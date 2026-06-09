#!/usr/bin/env bash
# ============================================================================
#  Crynova — первичная настройка чистого VDS (Ubuntu 22.04 / 24.04)
#  Запускать ОДИН РАЗ под root:  bash vps-setup.sh
#  Ставит: PHP 8.3 + расширения, Nginx, MySQL, Redis, Composer, Node 20,
#          Supervisor, certbot, firewall. Создаёт БД и пользователя деплоя.
# ============================================================================
set -euo pipefail

# ── НАСТРОЙКИ (поменяй под себя) ─────────────────────────────────────────────
DOMAIN="crynova.io"               # твой домен
APP_USER="crynova"                # системный пользователь приложения
APP_DIR="/var/www/crynova"        # куда клонируется проект
DB_NAME="crynova"
DB_USER="crynova"
DB_PASS="$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-20)"   # авто-пароль
PHP_VER="8.3"
# ─────────────────────────────────────────────────────────────────────────────

echo ">>> Crynova VPS setup for ${DOMAIN}"

export DEBIAN_FRONTEND=noninteractive
apt-get update -y && apt-get upgrade -y

# Базовые утилиты
apt-get install -y software-properties-common curl git unzip ufw ca-certificates lsb-release apt-transport-https gnupg

# ── PHP (ppa:ondrej/php) ────────────────────────────────────────────────────
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
  php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common \
  php${PHP_VER}-mysql php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-curl \
  php${PHP_VER}-bcmath php${PHP_VER}-intl php${PHP_VER}-zip php${PHP_VER}-gd \
  php${PHP_VER}-redis php${PHP_VER}-tokenizer

# ── Nginx ───────────────────────────────────────────────────────────────────
apt-get install -y nginx

# ── MySQL ───────────────────────────────────────────────────────────────────
apt-get install -y mysql-server
systemctl enable --now mysql
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

# ── Redis ───────────────────────────────────────────────────────────────────
apt-get install -y redis-server
systemctl enable --now redis-server

# ── Composer ────────────────────────────────────────────────────────────────
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ── Node 20 ─────────────────────────────────────────────────────────────────
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# ── Supervisor + certbot ────────────────────────────────────────────────────
apt-get install -y supervisor
systemctl enable --now supervisor
apt-get install -y certbot python3-certbot-nginx

# ── Пользователь приложения ─────────────────────────────────────────────────
id -u "${APP_USER}" &>/dev/null || adduser --disabled-password --gecos "" "${APP_USER}"
usermod -aG www-data "${APP_USER}"
mkdir -p "${APP_DIR}"
chown -R "${APP_USER}:www-data" "${APP_DIR}"

# ── Firewall ────────────────────────────────────────────────────────────────
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# ── SSH-ключ для деплоя (под пользователем приложения) ──────────────────────
sudo -u "${APP_USER}" bash -c '
  mkdir -p ~/.ssh && chmod 700 ~/.ssh
  [ -f ~/.ssh/id_ed25519 ] || ssh-keygen -t ed25519 -N "" -f ~/.ssh/id_ed25519
  ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null
'

echo "============================================================"
echo " ГОТОВО. Данные сохрани:"
echo "   Домен:        ${DOMAIN}"
echo "   Папка:        ${APP_DIR}"
echo "   Пользователь: ${APP_USER}"
echo "   БД:           ${DB_NAME}"
echo "   DB user:      ${DB_USER}"
echo "   DB pass:      ${DB_PASS}"
echo ""
echo " Deploy SSH public key (добавь в GitHub → Deploy keys, read-only):"
echo "------------------------------------------------------------"
cat /home/${APP_USER}/.ssh/id_ed25519.pub
echo "------------------------------------------------------------"
echo " Дальше: см. DEPLOY-GITHUB.md (клонирование и первый деплой)."
echo "============================================================"
