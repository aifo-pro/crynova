# Развёртывание Crynova на FastPanel

Пошаговая инструкция для VPS с установленным FastPanel.

## 0. Требования
- PHP **8.2+** (расширения: `bcmath`, `mbstring`, `openssl`, `pdo_mysql`, `curl`, `intl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `redis` опц.)
- MySQL/MariaDB
- Composer
- Node.js 18+ и npm (для сборки фронта)
- Redis (опционально — для очередей/кэша/сессий)

---

## 1. Создать сайт в FastPanel
1. **Сайты → Создать сайт**.
2. Домен: `crynova.io` (или твой).
3. Владелец/пользователь — создай отдельного системного пользователя сайта.
4. Версия PHP: **8.2** (или новее). Тип — **PHP-FPM**.
5. После создания корень сайта будет вида:
   `/var/www/USER/data/www/crynova.io/`

## 2. Указать корень документов на /public
Laravel должен отдаваться из папки `public`, а не из корня.

- **Сайты → твой сайт → Настройки → Корневая директория (Document Root):**
  установить `.../crynova.io/public`

Либо, если меняешь только Nginx — добавь правило (см. п.7).

## 3. Загрузить код
Вариант А (Git — рекомендуется):
```bash
cd /var/www/USER/data/www/crynova.io
git clone <твой-репозиторий> .
```
Вариант Б (архив): загрузи ZIP через **Файловый менеджер**, распакуй в корень сайта.

> Не загружай `vendor/`, `node_modules/`, `.env` — их сгенерируем на сервере.

## 4. Создать базу данных
**Базы данных → Создать БД**: имя `crynova`, пользователь, пароль. Запиши их.

## 5. Установить зависимости и собрать фронт
Открой терминал (FastPanel → SSH, или по SSH под пользователем сайта):
```bash
cd /var/www/USER/data/www/crynova.io

# PHP-зависимости (без dev для прода)
composer install --no-dev --optimize-autoloader

# Фронт (нужно один раз — соберёт CSS/JS, включая Alpine.js)
npm ci
npm run build
```

## 6. Настроить .env
```bash
cp .env.example .env   # если есть; иначе создай вручную
php artisan key:generate
nano .env
```
Заполни:
```dotenv
APP_NAME=Crynova
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crynova.io      # ВАЖНО: реальный домен с https

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crynova
DB_USERNAME=твой_пользователь
DB_PASSWORD=твой_пароль

# Если есть Redis:
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
# Если Redis нет — оставь:
# CACHE_STORE=database
# SESSION_DRIVER=database
# QUEUE_CONNECTION=database

SESSION_ENCRYPT=true

# Узлы блокчейна (для реального приёма платежей):
# BTC_RPC_URL=, ETH_RPC_URL=, TRON_API_URL= и т.д.

# Telegram-уведомления (опц.):
# TELEGRAM_BOT_TOKEN=
# TELEGRAM_CHAT_ID=
```

## 7. Применить миграции и подготовить хранилище
```bash
php artisan migrate --force
php artisan db:seed --force          # создаст валюты, дефолтные настройки, админа
php artisan storage:link             # публичная папка для логотипов
```
> Сидер создаёт админа `admin@crynova.io` / `changeme123!` — **смени пароль сразу** после первого входа.

## 8. Кэширование (ускорение прода)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> При каждом обновлении кода повторяй `php artisan optimize:clear` затем эти три команды (или просто `php artisan optimize`).

## 9. Права на запись
```bash
chmod -R 775 storage bootstrap/cache
# владелец — пользователь сайта (подставь своего):
chown -R USER:USER storage bootstrap/cache
```

## 10. Nginx (если Document Root нельзя сменить на /public)
В FastPanel: **Сайт → Nginx → Конфигурация**, добавь/проверь:
```nginx
root /var/www/USER/data/www/crynova.io/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
Сохрани и перезапусти Nginx (кнопка в панели).

## 11. SSL
**Сайт → SSL → Let's Encrypt** → выпустить сертификат, включить «Принудительный HTTPS».
(в коде уже включён `URL::forceScheme('https')` в проде).

## 12. Очереди (worker) — через Supervisor
Платежи, вебхуки и проверки идут через очереди. Создай Supervisor-программу
(FastPanel → **Supervisor**, либо вручную `/etc/supervisor/conf.d/crynova.conf`):
```ini
[program:crynova-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/USER/data/www/crynova.io/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=USER
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/USER/data/www/crynova.io/storage/logs/worker.log
stopwaitsecs=3600
```
```bash
supervisorctl reread && supervisorctl update && supervisorctl start crynova-worker:*
```

## 13. Планировщик (cron)
Добавь cron-задачу (FastPanel → **Cron**, под пользователем сайта):
```cron
* * * * * cd /var/www/USER/data/www/crynova.io && php artisan schedule:run >> /dev/null 2>&1
```
Это запускает: опрос инвойсов, истечение просроченных, ретраи вебхуков.

## 14. Проверка
- Открой `https://crynova.io` — главная.
- `https://crynova.io/login` — вход (`admin@crynova.io` / `changeme123!`).
- В админке смени пароль и заполни **Настройки сайта**.
- `https://crynova.io/robots.txt` и `/sitemap.xml` — должны отдаваться.

---

## Обновление кода в будущем
```bash
cd /var/www/USER/data/www/crynova.io
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
supervisorctl restart crynova-worker:*   # перезапуск воркеров
```

## Частые проблемы
- **500 / белый экран** → `APP_DEBUG=true` временно, смотри `storage/logs/laravel.log`; проверь права на `storage`.
- **Стили/JS не грузятся** → не выполнен `npm run build`, либо `APP_URL` неверный.
- **404 на новых страницах** → устаревший route-кэш: `php artisan route:clear`.
- **Ссылки ведут на `localhost`** → неверный `APP_URL` в `.env` (+ `php artisan config:clear`).
- **Платёж/вывод не создаётся** → не настроены узлы блокчейна (RPC) в `.env`.
