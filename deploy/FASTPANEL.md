# Crynova — FastPanel deployment guide

## 1. FastPanel site settings

| Setting | Value |
|---------|-------|
| Document root | `/var/www/<user>/data/www/<domain>/public` |
| PHP version | 8.3+ |
| HTTPS | Enable (Let's Encrypt) |

**Important:** document root must be `public/`, not the project root.

### Симптом: скачивается файл `login` (543 байт) вместо страницы

**543 байта — это размер файла `public/index.php`.** Браузер скачивает его «как есть», без выполнения PHP.
Главная `/` может открываться (через `index.php`), а `/login`, `/register` и другие URL — **скачиваются**.
Причина: Nginx не передаёт запросы в `public/index.php` (нет `try_files`) или PHP-FPM не обрабатывает эти запросы.

### Разбор типового конфига FastPanel (crynova.io)

Если в основном конфиге сайта уже есть:

```nginx
set $root_path /var/www/crynova_io_usr/data/www/crynova.io/public;
root $root_path;
index index.php;

location / {
    try_files $uri /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/crynova.io.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include /etc/nginx/fastcgi_params;
}
```

— **это уже правильно.** Не добавляйте второй блок `location /` в «Дополнительные директивы» — дубликат может ломать маршруты.

Рекомендуемые правки (в блок `location /` и `location ~ \.php$` основного конфига или через includes):

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    try_files $uri =404;
    fastcgi_pass unix:/var/run/crynova.io.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include /etc/nginx/fastcgi_params;
}
```

Проверка на сервере по SSH:

```bash
curl -sI https://crynova.io/login | grep -i content-type
# Ожидается: Content-Type: text/html; charset=utf-8

curl -s https://crynova.io/login | wc -c
# Ожидается: ~6000+ байт, не 543
```

Если `curl` на сервере OK, а в Chrome всё ещё скачивается — очистите кеш / откройте инкognito (старые ответы могли закешироваться).

**Исправление в FastPanel (если try_files ещё нет):**

1. Сайты → **crynova.io** → корневая папка: `.../crynova.io/public`
2. PHP: **8.3+**, обработчик **PHP-FPM** (не «Статический»)
3. Только если в основном конфиге **нет** `try_files` — добавьте директивы из `deploy/nginx-fastpanel-extra.conf`
4. Перезагрузите Nginx (или «Применить» в панели)
5. По SSH:
   ```bash
   cd /var/www/crynova_io_usr/data/www/crynova.io
   php artisan config:clear
   php artisan route:clear
   php artisan config:cache
   php artisan route:cache
   ```

**Проверка:**

- https://crynova.io/check.php — должен показать JSON (`ok: true`). Потом удалите `public/check.php`.
- https://crynova.io/up — Laravel health check
- https://crynova.io/login — форма входа (не скачивание)

If the browser **downloads a file** instead of opening a page (e.g. a file named `login`), check:

1. Document root points to **`public/`**, not the project folder.
2. PHP-FPM is enabled for the site (FastPanel → PHP → handler must be `php-fpm`, not static).
3. Nginx `try_files` block from `deploy/nginx-fastpanel.conf` is applied.
4. After fixing `.env` / config, run: `php artisan config:clear && php artisan config:cache`.
5. PHP **8.3+** is required; Crynova's `config/database.php` is compatible with both 8.3 and 8.4.

## 2. Upload / clone project

```bash
cd /var/www/<user>/data/www/<domain>
git clone <repo-url> .
# or upload files excluding vendor/ and node_modules/
```

## 3. Install

```bash
bash deploy/install.sh /var/www/<user>/data/www/<domain>
```

Edit `.env` before or after first run:

```bash
nano .env
php artisan config:cache
php artisan migrate --force
```

## 4. Queue worker

### Supervisor (recommended)

```bash
sudo cp deploy/supervisor/crynova-worker.conf /etc/supervisor/conf.d/
# Edit paths in the config file
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start crynova-worker:*
```

### systemd alternative

```bash
sudo cp deploy/systemd/crynova-worker.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now crynova-worker
```

## 5. Scheduler cron

Add to crontab of the site user (FastPanel → Cron):

```
* * * * * cd /var/www/<user>/data/www/<domain> && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks:
- `crynova:poll-invoices` — every minute
- `crynova:expire-invoices` — every minute
- `crynova:retry-webhooks` — every 5 minutes

## 6. Post-deploy smoke test

```bash
php artisan about
php artisan migrate:status
curl -s https://your-domain.com/up
curl -s -H "Authorization: Bearer cryn_xxx" https://your-domain.com/api/v1/invoices/UUID
```

## 7. Default admin (change immediately)

After `php artisan db:seed`:
- Email: `admin@crynova.io`
- Password: `changeme123!`

```bash
php artisan tinker --execute="App\Models\User::where('email','admin@crynova.io')->update(['password'=>bcrypt('NEW_STRONG_PASSWORD')]);"
```
