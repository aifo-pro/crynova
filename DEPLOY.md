# Crynova — Deployment Guide (FastPanel VPS)

## 1. Server Requirements
- PHP 8.3+ with extensions: pdo_mysql, redis, gmp, bcmath, mbstring, openssl, curl, intl
- MySQL 8.0 / MariaDB 10.6+
- Redis 7+
- Node.js 20+ (build only)
- Supervisor (for queue workers)

## 2. FastPanel Setup
1. Create site in FastPanel → choose domain
2. Set document root to `/path/to/crynova/public`
3. PHP version: 8.3+
4. Create MySQL database `crynova` and user

## 3. Deploy Steps

```bash
git clone https://github.com/yourorg/crynova.git /var/www/crynova
cd /var/www/crynova

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Edit .env with DB/Redis/node credentials
nano .env

npm install && npm run build

php artisan migrate --force
php artisan db:seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. Nginx Config (FastPanel adds automatically, adjust as needed)

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## 5. Supervisor — Queue Workers

Create `/etc/supervisor/conf.d/crynova.conf`:

```ini
[program:crynova-blockchain]
command=php /var/www/crynova/artisan queue:work redis --queue=blockchain --sleep=3 --tries=3 --timeout=60
user=www-data
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/crynova-blockchain.log

[program:crynova-webhooks]
command=php /var/www/crynova/artisan queue:work redis --queue=webhooks --sleep=3 --tries=1 --timeout=30
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/crynova-webhooks.log

[program:crynova-default]
command=php /var/www/crynova/artisan queue:work redis --queue=default --sleep=3 --tries=3
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/crynova-default.log
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start all
```

## 6. Scheduler (Crontab)

```
* * * * * www-data php /var/www/crynova/artisan schedule:run >> /dev/null 2>&1
```

## 7. HD Wallet Setup (CRITICAL)

**NEVER store private keys in .env or database.**

For Bitcoin/LTC/DOGE:
1. Install and sync full node (bitcoind, litecoind, dogecoind)
2. Create HD wallet: `bitcoin-cli createwallet "crynova" false false "" false true`
3. Set RPC credentials in .env

For ETH/BSC:
1. Use Infura/Alchemy endpoint OR run geth/bsc-node
2. Store HD master xpub in settings table (encrypted):
   `php artisan tinker` → `App\Models\Setting::set('eth.master_xpub', 'xpub...', true);`

For TRON:
1. Set TRON_API_KEY from TronGrid
2. Store master xpub same as ETH

## 8. Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `SESSION_ENCRYPT=true`
- [ ] Firewall: block all ports except 80, 443, 22
- [ ] Block direct access to RPC ports (8332, etc.) from internet
- [ ] Set up SSL cert via FastPanel (Let's Encrypt)
- [ ] Change default admin password immediately after first login
- [ ] Set up 2FA for admin account
- [ ] Rotate `APP_KEY` is NOT needed after first deploy (breaks existing encrypted data)
- [ ] Store `HD_MASTER_KEY_ENCRYPTED` in a secrets manager (Vault/AWS SSM) not in .env on prod

## 9. First Run

```bash
# Create admin user (seeder sets admin@crynova.io / changeme123!)
php artisan db:seed --class=AdminSeeder

# IMMEDIATELY change password in /admin/settings or via tinker
php artisan tinker
>>> App\Models\User::where('email','admin@crynova.io')->first()->update(['password' => bcrypt('YOUR_STRONG_PASSWORD')]);
```
