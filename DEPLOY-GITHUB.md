# Crynova — установка на VDS «с нуля» через GitHub

Полный путь: создаём репозиторий → настраиваем чистый сервер → клонируем →
первый деплой → авто-деплой по `git push`.

---

## Часть 1. GitHub — создать репозиторий и залить код

### 1.1 Создать репозиторий
1. GitHub → **New repository** → имя `crynova` → **Private** → Create.
2. Скопируй URL вида `git@github.com:USER/crynova.git`.

### 1.2 Залить код (на своём компьютере, в папке проекта)
```bash
cd C:\Users\Denys\Desktop\Crynova        # путь к проекту
git init
git add .
git commit -m "Initial Crynova"
git branch -M main
git remote add origin git@github.com:USER/crynova.git
git push -u origin main
```
> `vendor/`, `node_modules/`, `.env` **не пушатся** — они в `.gitignore` (Laravel). Это правильно.

---

## Часть 2. Сервер — первичная настройка (один раз)

Нужен чистый VDS на **Ubuntu 22.04 / 24.04**, доступ по root (SSH).

### 2.1 Запустить скрипт настройки
```bash
ssh root@IP_СЕРВЕРА
# залить скрипт (или скопировать содержимое deploy/vps-setup.sh)
nano vps-setup.sh        # вставить содержимое, поправить DOMAIN вверху
bash vps-setup.sh
```
Скрипт поставит PHP 8.3, Nginx, MySQL, Redis, Composer, Node 20, Supervisor,
certbot, firewall, создаст БД и пользователя `crynova`, сгенерирует SSH-ключ.

В конце он напечатает:
- **данные БД** (имя/пользователь/пароль) — сохрани;
- **Deploy SSH public key** — понадобится для доступа сервера к GitHub.

### 2.2 Дать серверу доступ к приватному репозиторию
GitHub → репозиторий → **Settings → Deploy keys → Add deploy key**:
- вставь публичный ключ (тот, что напечатал скрипт);
- галку «Allow write access» **не ставь** (read-only достаточно).

---

## Часть 3. Первый деплой

```bash
# на сервере, под пользователем приложения
su - crynova
cd /var/www/crynova

git clone git@github.com:USER/crynova.git .

# первый запуск создаст .env и остановится
bash deploy/deploy.sh

# заполнить .env
nano .env
```
В `.env` обязательно:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crynova.io          # реальный домен с https!

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=crynova
DB_USERNAME=crynova
DB_PASSWORD=<пароль из vps-setup>

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_ENCRYPT=true
```
Запустить деплой ещё раз (теперь пройдёт миграции, сидинг, кэш):
```bash
bash deploy/deploy.sh
```

---

## Часть 4. Nginx + SSL

```bash
# под root
cp /var/www/crynova/deploy/nginx.conf.example /etc/nginx/sites-available/crynova
nano /etc/nginx/sites-available/crynova        # проверь домен и версию php-fpm
ln -s /etc/nginx/sites-available/crynova /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# SSL (домен уже должен указывать A-записью на IP сервера)
certbot --nginx -d crynova.io -d www.crynova.io
```

---

## Часть 5. Очереди (Supervisor) и Cron

```bash
# под root
cp /var/www/crynova/deploy/crynova-worker.conf /etc/supervisor/conf.d/
supervisorctl reread && supervisorctl update
supervisorctl start crynova-worker:*

# Cron планировщика (под пользователем crynova)
crontab -u crynova -e
# добавить строку:
* * * * * cd /var/www/crynova && php artisan schedule:run >> /dev/null 2>&1
```

> Чтобы `deploy.sh` мог перезапускать воркеры без пароля, разреши команду в sudoers:
> `echo 'crynova ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl' > /etc/sudoers.d/crynova`

---

## Часть 6. Авто-деплой по `git push` (GitHub Actions)

Workflow уже в репозитории: `.github/workflows/deploy.yml`.
Добавь **Secrets** (GitHub → репозиторий → Settings → Secrets and variables → Actions → New):

| Secret      | Значение                                              |
|-------------|-------------------------------------------------------|
| `SSH_HOST`  | IP сервера                                            |
| `SSH_USER`  | `crynova`                                             |
| `SSH_PORT`  | `22` (если нестандартный — укажи свой)                |
| `SSH_KEY`   | приватный SSH-ключ с доступом к серверу (см. ниже)    |

**Как получить `SSH_KEY`:** на своём ПК создай отдельную пару для CI и добавь
публичную часть в `~/.ssh/authorized_keys` пользователя `crynova` на сервере:
```bash
ssh-keygen -t ed25519 -f crynova_ci -N ""
# crynova_ci.pub → на сервер:  cat >> /home/crynova/.ssh/authorized_keys
# crynova_ci (приватный) → вставить целиком в Secret SSH_KEY
```

Теперь каждый `git push` в `main` автоматически запускает деплой на сервере.

---

## Обновление вручную
```bash
su - crynova && cd /var/www/crynova && bash deploy/deploy.sh
```

## Проверка
- `https://crynova.io` — главная
- `https://crynova.io/login` — `admin@crynova.io` / `changeme123!` (**смени пароль!**)
- `https://crynova.io/sitemap.xml`, `/robots.txt`, `/favicon.svg`

## Типичные ошибки
- **500 / белый экран** → `tail -f storage/logs/laravel.log`; права `chmod -R 775 storage bootstrap/cache`.
- **Стили/JS не грузятся** → не прошёл `npm run build` или неверный `APP_URL`.
- **404 на новых страницах** → `php artisan route:clear` (или `optimize:clear`).
- **Ссылки на `localhost`** → неверный `APP_URL` в `.env` + `php artisan config:clear`.
- **Платёж/вывод не создаётся** → не настроены RPC-узлы блокчейна в `.env`.
