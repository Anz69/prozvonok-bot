#!/usr/bin/env bash
#
# Prozvonok Bot — установка на чистый Ubuntu в одну команду (Docker).
# Все секреты передаются переменными окружения перед запуском скрипта.
#
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/Anz69/prozvonok-bot.git}"
DIR="${DIR:-/opt/prozvonok-bot}"

# --- обязательное ---
: "${TELEGRAM_TOKEN:?Укажите TELEGRAM_TOKEN}"

TELEGRAM_BOT_USERNAME="${TELEGRAM_BOT_USERNAME:-}"
# Домен для HTTPS (Caddy + Let's Encrypt, домен смотрит прямо на сервер). Нужен для Mini App.
# По умолчанию — radistka.pro. APP_URL/MINIAPP_URL формируются автоматически.
# Порты 80 и 443 должны быть открыты (ACME-проверка Let's Encrypt).
APP_DOMAIN="${APP_DOMAIN:-radistka.pro}"
if [ -n "${APP_DOMAIN}" ]; then
    APP_URL="${APP_URL:-https://${APP_DOMAIN}}"
    MINIAPP_URL="${MINIAPP_URL:-${APP_URL}/app}"
    SESSION_SECURE_COOKIE="true"
else
    APP_URL="${APP_URL:-http://localhost}"
    MINIAPP_URL="${MINIAPP_URL:-}"
    SESSION_SECURE_COOKIE="false"
fi
ADMIN_IDS="${ADMIN_IDS:-}"
MANAGER_URL="${MANAGER_URL:-}"
CHANNEL_URL="${CHANNEL_URL:-}"
REQUIRED_CHANNEL="${REQUIRED_CHANNEL:-}"
ZVONOK_API_KEY="${ZVONOK_API_KEY:-}"
ZVONOK_CAMPAIGN_ID="${ZVONOK_CAMPAIGN_ID:-}"
ZVONOK_DRIVER="${ZVONOK_DRIVER:-fake}"
TELEGRAM_POLL_TIMEOUT="${TELEGRAM_POLL_TIMEOUT:-20}"

echo "==> Установка пакетов (docker, git)…"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y >/dev/null
apt-get install -y git curl openssl >/dev/null
if ! command -v docker >/dev/null 2>&1; then
    curl -fsSL https://get.docker.com | sh
fi

echo "==> Получение кода в ${DIR}…"
if [ -d "${DIR}/.git" ]; then
    git -C "${DIR}" pull --ff-only
else
    git clone --depth 1 "${REPO_URL}" "${DIR}"
fi
cd "${DIR}"

if [ ! -f .env ]; then
    echo "==> Генерация .env…"
    APP_KEY="base64:$(openssl rand -base64 32)"
    DB_PASSWORD="$(openssl rand -hex 16)"
    DB_ROOT_PASSWORD="$(openssl rand -hex 16)"
    ADMIN_PASSWORD="$(openssl rand -hex 8)"
    POSTBACK_SECRET="$(openssl rand -hex 12)"

    cat > .env <<ENV
APP_NAME="Prozvonok"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}
APP_DOMAIN=${APP_DOMAIN}
APP_LOCALE=ru

# --- Mini App (Telegram WebApp) ---
# HTTPS-ссылка на /app. Пусто = кнопка запуска не показывается.
MINIAPP_URL=${MINIAPP_URL}

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=dozvon
DB_USERNAME=dozvon
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE}
QUEUE_CONNECTION=database
CACHE_STORE=database
REDIS_HOST=redis

ADMIN_EMAIL=admin@prozvonok.local
ADMIN_PASSWORD=${ADMIN_PASSWORD}

# --- Telegram ---
TELEGRAM_TOKEN=${TELEGRAM_TOKEN}
TELEGRAM_BOT_USERNAME=${TELEGRAM_BOT_USERNAME}
TELEGRAM_LOG_CHANNEL=null
TELEGRAM_POLL_TIMEOUT=${TELEGRAM_POLL_TIMEOUT}

# --- Конфиг бота (читается сидерами) ---
ADMIN_IDS=${ADMIN_IDS}
MANAGER_URL=${MANAGER_URL}
CHANNEL_URL=${CHANNEL_URL}
REQUIRED_CHANNEL=${REQUIRED_CHANNEL}

# --- Звонок.com ---
ZVONOK_DRIVER=${ZVONOK_DRIVER}
ZVONOK_BASE_URL=https://zvonok.com/manager/cabapi_external/api/v1
ZVONOK_API_KEY=${ZVONOK_API_KEY}
ZVONOK_CAMPAIGN_ID=${ZVONOK_CAMPAIGN_ID}
ZVONOK_RATE_LIMIT=20
ZVONOK_POSTBACK_SECRET=${POSTBACK_SECRET}

# --- USDT (опц., оплата идёт через менеджера) ---
USDT_WALLET=
USDT_PROVIDER=manual
ENV
    echo "    Пароль админ-панели сохранён в .env (ADMIN_PASSWORD=${ADMIN_PASSWORD})"
fi

echo "==> Сборка фронтенда Mini App (Vite)…"
# Node не ставим на хост — собираем во временном контейнере. Ассеты ложатся в ./public/build
# (этот же каталог монтируется в app/nginx), поэтому доступны без пересборки образа.
docker run --rm -v "${DIR}:/app" -w /app node:22-alpine sh -c 'npm ci && npm run build'

echo "==> Сборка и запуск контейнеров…"
docker compose up -d --build

echo "==> Установка зависимостей в контейнере…"
docker compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction || true
docker compose exec -T app chown -R www-data:www-data storage bootstrap/cache || true
docker compose exec -T app chmod -R 775 storage bootstrap/cache || true
docker compose exec -T app php artisan config:clear || true
docker compose exec -T app php artisan view:clear || true
docker compose exec -T app php artisan filament:upgrade --ansi || true

echo "==> Ожидание базы данных…"
for i in $(seq 1 40); do
    if docker compose exec -T db sh -c 'mysqladmin ping -uroot -p"$MYSQL_ROOT_PASSWORD" --silent' >/dev/null 2>&1; then
        break
    fi
    sleep 3
done

echo "==> Миграции и сидеры…"
docker compose exec -T app php artisan migrate --force --seed

echo "==> Сброс webhook (нужен polling) и проверка токена…"
docker compose exec -T app php artisan tinker --execute='try{$b=app(SergiX44\Nutgram\Nutgram::class);$b->deleteWebhook();$m=$b->getMe();echo "OK @".$m->username;}catch(\Throwable $e){echo "TG ERROR: ".$e->getMessage();}' || true

if [ -n "${MINIAPP_URL}" ]; then
    echo "==> Настройка кнопки-меню бота на Mini App (${MINIAPP_URL})…"
    docker compose exec -T app php artisan bot:menu:set "${MINIAPP_URL}" || true
fi

echo "==> Перезапуск бота/воркеров…"
docker compose restart bot queue scheduler
sleep 5
docker compose logs --tail=15 bot || true

IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
echo
echo "================================================================"
echo " ✅ Готово!"
echo " Бот: long-polling запущен (сервис bot)."
if [ -n "${MINIAPP_URL}" ]; then
    echo " Mini App: ${MINIAPP_URL} (кнопка «🚀 Открыть приложение» в боте)"
else
    echo " Mini App: отключён (задайте APP_DOMAIN для HTTPS, чтобы включить)"
fi
echo " Админка: http://${IP:-SERVER_IP}:8080/admin"
echo "   логин:  admin@prozvonok.local"
echo "   пароль: см. ADMIN_PASSWORD в ${DIR}/.env"
echo
echo " Канал обязательной подписки: ${REQUIRED_CHANNEL:-—}"
echo "   ⚠️  Добавьте бота АДМИНОМ в этот канал, иначе проверка подписки не сработает."
echo " Реальный обзвон: в ${DIR}/.env установите ZVONOK_DRIVER=http,"
echo "   затем: docker compose restart queue scheduler"
echo "================================================================"
