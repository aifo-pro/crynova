#!/usr/bin/env bash
# Crynova — first-time VPS install (FastPanel / Ubuntu)
# Usage: bash deploy/install.sh /var/www/crynova/data/www/your-domain.com

set -euo pipefail

APP_DIR="${1:-$(pwd)}"
WEB_USER="${WEB_USER:-www-data}"

echo "==> Installing Crynova in ${APP_DIR}"

cd "${APP_DIR}"

# ── PHP dependencies ─────────────────────────────────────────────────
composer install --no-dev --optimize-autoloader --no-interaction

# ── Environment ──────────────────────────────────────────────────────
if [[ ! -f .env ]]; then
    cp .env.example .env
    php artisan key:generate --force
    echo "!! Edit .env (DB, APP_URL, node RPC, mail) before continuing."
fi

# ── Frontend assets ───────────────────────────────────────────────────
npm ci --ignore-scripts
npm run build

# ── Laravel bootstrap ─────────────────────────────────────────────────
php artisan storage:link --force
php artisan migrate --force
php artisan view:clear
php artisan config:cache
php artisan route:cache
# Do NOT view:cache here — run after deploy when assets are final:
# php artisan view:cache

# ── Permissions (FastPanel: site user may differ from www-data) ───────
chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "==> Done. Next steps:"
echo "    1. Point FastPanel document root to: ${APP_DIR}/public"
echo "    2. If try_files is missing, see deploy/FASTPANEL.md (do NOT duplicate location /)"
echo "    3. Enable HTTPS in FastPanel"
echo "    4. Install supervisor config: deploy/supervisor/crynova-worker.conf"
echo "    5. Add cron: * * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
echo "    6. Change default admin password (admin@crynova.io)"
echo "    7. Remove public/check.php after verifying PHP works"
