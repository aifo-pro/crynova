#!/usr/bin/env bash
# ============================================================================
#  Crynova — деплой / обновление. Запускать под пользователем приложения:
#     cd /var/www/crynova && bash deploy/deploy.sh
#  Идемпотентен: подтягивает git, ставит зависимости, билдит фронт,
#  применяет миграции, пересобирает кэш и перезапускает воркеры.
# ============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/crynova}"
BRANCH="${BRANCH:-main}"
REPO="${REPO:-}"          # напр. REPO=git@github.com:USER/crynova.git (нужно только для первого клона)

# ── Первый запуск: клонировать репозиторий, если его ещё нет ────────────────
if [ ! -d "${APP_DIR}/.git" ]; then
    echo ">>> ${APP_DIR} ещё не git-репозиторий."
    if [ -z "${REPO}" ]; then
        echo "!!! Укажи репозиторий и запусти снова, напр.:"
        echo "    REPO=git@github.com:USER/crynova.git bash deploy/deploy.sh"
        exit 1
    fi
    mkdir -p "${APP_DIR}"
    # клон во временную папку и перенос (на случай, если APP_DIR не пуст)
    if [ -z "$(ls -A "${APP_DIR}" 2>/dev/null)" ]; then
        git clone --branch "${BRANCH}" "${REPO}" "${APP_DIR}"
    else
        git clone --branch "${BRANCH}" "${REPO}" /tmp/crynova_clone
        cp -a /tmp/crynova_clone/. "${APP_DIR}/"
        rm -rf /tmp/crynova_clone
    fi
fi

cd "${APP_DIR}"

echo ">>> Pulling ${BRANCH}..."
git fetch --all
git reset --hard "origin/${BRANCH}"

echo ">>> Composer (prod)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo ">>> Frontend build..."
npm ci
npm run build

# .env / ключ приложения (первый запуск)
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --force
  echo "!!! Заполни .env (APP_URL, DB_*, Redis) и запусти deploy ещё раз."
  exit 1
fi

echo ">>> Migrations..."
php artisan migrate --force

# Один раз: симлинк storage + сидинг (валюты/настройки/админ)
[ -L public/storage ] || php artisan storage:link
php artisan db:seed --force || true

echo ">>> Cache rebuild..."
php artisan optimize:clear
php artisan optimize

echo ">>> Permissions..."
chmod -R 775 storage bootstrap/cache || true

echo ">>> Restart queue workers..."
( command -v supervisorctl >/dev/null && sudo supervisorctl restart crynova-worker:* ) || true

echo ">>> DONE."
