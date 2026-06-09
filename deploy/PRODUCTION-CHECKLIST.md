# Crynova Production Checklist

## Environment

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated (`php artisan key:generate`)
- [ ] `APP_URL=https://your-domain.com` (HTTPS, no trailing slash)
- [ ] `LOG_LEVEL=warning` or `error`

## Database

- [ ] MySQL/MariaDB credentials set in `.env`
- [ ] `php artisan migrate --force` completed
- [ ] `php artisan db:seed` (then change default admin password)
- [ ] Database user has minimal privileges (SELECT, INSERT, UPDATE, DELETE, no DROP)

## Web server (FastPanel)

- [ ] Document root → `public/`
- [ ] HTTPS enabled (Let's Encrypt)
- [ ] PHP 8.3+
- [ ] Required extensions: `bcmath`, `curl`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`

## Laravel bootstrap

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build` (upload entire `public/build/` folder)
- [ ] `php artisan view:clear` **after every frontend rebuild** (stale view cache breaks CSS/JS URLs)
- [ ] `php artisan storage:link`
- [ ] `storage/` and `bootstrap/cache/` writable by web user
- [ ] `php artisan config:cache && php artisan route:cache`
- [ ] Run **`php artisan crynova:doctor`** — all green before go-live

### Quick VPS diagnosis

```bash
cd /var/www/crynova_io_usr/data/www/crynova.io
php artisan crynova:doctor
```

Or open `https://crynova.io/check.php` (delete after use).

### UI works locally but not on VPS (toggles, tabs, layout)

Usually one of:

1. **`npm run build` not run on server** — Alpine.js lives in `public/build/assets/app-*.js`
2. **`php artisan view:cache` with old asset hashes** — run `php artisan view:clear`
3. **Missing PHP `bcmath`** — balances and crypto math fail silently
4. **Only uploaded PHP files, not `public/build/`** — always deploy `manifest.json` + assets together

```bash
npm ci && npm run build
php artisan view:clear
php artisan config:cache
```

## Queue & scheduler

- [ ] `QUEUE_CONNECTION=database` (or redis)
- [ ] Supervisor/systemd worker running (`queue:work`)
- [ ] Cron: `* * * * * php artisan schedule:run`
- [ ] Worker log monitored: `storage/logs/worker.log`

## Security

- [ ] Default admin password changed
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_ENCRYPT=true`
- [ ] API keys only via `Authorization: Bearer` or `X-Api-Key` header
- [ ] Merchant webhook secrets stored encrypted (APP_KEY required)
- [ ] `.env` not in git, permissions `600`
- [ ] Private keys / HD seeds only in encrypted `settings` table or node wallet — never in git
- [ ] `storage/*.key` in `.gitignore`
- [ ] Rate limiting active on `/api/*` (60 req/min per API key)
- [ ] 2FA enabled for admin accounts
- [ ] IP whitelist on production API keys (recommended)

## Blockchain nodes

- [ ] HD account xpubs configured in **Admin → Settings → HD-гаманці** (or `HD_XPUB_*` in `.env`)
- [ ] `php artisan crynova:generate-addresses --count=50` run after xpub setup
- [ ] BTC/LTC/DOGE RPC reachable from VPS (fallback if xpub missing)
- [ ] ETH node URL + `ETHERSCAN_API_KEY` (and `BSCSCAN_API_KEY` for BEP-20)
- [ ] TRON `TRON_API_KEY` set for TronGrid rate limits
- [ ] Confirmations configured per currency in `.env` and `currencies` table

## API integration (merchant side)

- [ ] Verify webhook signature: `X-Crynova-Sig: sha256=<hmac_sha256(body, webhook_secret)>`
- [ ] Use `Idempotency-Key` header on `POST /api/v1/invoices`
- [ ] Handle events: `invoice.paid`, `invoice.underpaid`, `invoice.overpaid`, `invoice.expired`, `invoice.waiting_confirmations`

## Monitoring

- [ ] `GET /up` health check in uptime monitor
- [ ] Log rotation for `storage/logs/laravel.log` and worker log
- [ ] Alert on failed webhook retries (`webhook_logs.success = 0`)

## Backup

- [ ] Daily MySQL dump
- [ ] Encrypted backup of `.env` and node wallet backups (off-server)

## Pre-launch test

- [ ] Create test invoice via API
- [ ] Sandbox: enable merchant **test_mode**, then `php artisan crynova:simulate-payment {uuid}`
- [ ] Send test payment (testnet or small mainnet amount)
- [ ] Confirm status transitions: pending → waiting_confirmations → paid
- [ ] Confirm webhook delivered with valid signature
- [ ] Confirm invoice expiration after TTL
- [ ] Test underpaid/overpaid scenarios
