# Сол Гудман / Dozvon Bot

Telegram-бот проверки баз телефонных номеров через сервис **Звонок.com**, с предоплатным
балансом (USDT TRC-20), реферальной системой, премиум-подписками и полным управлением
через **Filament**-админку.

Стек: **Laravel 13 · Nutgram · Filament 5 · PhpSpreadsheet · libphonenumber**.

> Принцип проекта: всё, что в ТЗ «настраивается в админке» (тексты, тарифы, лимиты,
> кнопки, капча, ссылки), читается из БД через `Setting` / `BotText` / `BotButton` и
> правится без кода. Бот — тонкий роутер, логика — в `app/Services`.

---

## Что реализовано

**Ядро (Part 1)**
- **Домен:** все сущности из ТЗ (миграции + модели) — пользователи, балансы, транзакции,
  платежи, выводы, задания/номера проверки, гео/тарифы, бонусы, тексты, кнопки, каналы,
  обращения, аудит.
- **Слой настроек/текстов:** `Setting::get()`, `BotText::render()`, сидеры всех эталонных
  текстов (Приложения А/Б ТЗ) и параметров.
- **Сервисы:** `BalanceService` (атомарные движения + транзакции), `PricingService`,
  `ReferralService`, `OnboardingService`, `NumberFileParser` (E.164, дедуп), `WorkingHours`.
- **Бот (Nutgram):** онбординг (подписка → капча 🍍), главное меню, read-only экраны
  (Профиль/Досье, Баланс, Реф.система, Инфо, Премиум), Калькулятор.
- **Filament-админка:** ресурсы для всех сущностей с русской навигацией; ручные операции.

**Деньги (Part 2)**
- **Приём USDT TRC-20:** драйвер `UsdtWatcher` (`fake`/`tronscan`), уникальная сумма счёта
  для матчинга, команда `payments:poll` → авто-зачисление + бонус лояльности + реф-начисление
  + уведомление; идемпотентность по `tx_hash`; счёт с QR-кодом.
- **Вывод реф.баланса:** `WithdrawConversation` с резервом средств, одобрение/отклонение в
  Filament (возврат при отклонении).
- **Премиум/Премиум+:** `PremiumService` (списание с депозита, скидка, срок), команда
  `premium:expire` (уведомление за день + деактивация + опц. авто-продление).

**Звонок.com (Part 3)**
- Клиент `ZvonokClient` (`fake`/`http`, троттлинг ≤20 rps, ретраи на 429).
- `ProcessCheckJob` (массовая постановка с учётом timezone), `checks:dispatch-scheduled`.
- Postback-webhook `POST /webhooks/zvonok/{secret}` + polling-фолбэк `checks:poll-results`,
  маппинг статусов из настроек.
- Агрегация сводки + экспорт `.xlsx` по статусам (`CheckResultExporter`) → отправка файла.

**Полировка (Part 4)**
- Анти-флуд `ThrottleUser`, лог-каналы `integration`/`payments`, расписание в
  `routes/console.php`, тесты денежных и интеграционных путей.

Точные эндпоинты внешних API помечены `// TODO` — подставляются по докам Звонок.com и при
выборе USDT-провайдера. По умолчанию драйверы `fake` → весь флоу работает локально.

---

## Запуск

```bash
composer install
cp .env.example .env        # заполните секреты
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

БД по умолчанию — **SQLite** (работает из коробки, без настройки). Для MySQL/PostgreSQL
поменяйте `DB_*` в `.env`.

### Админка

`http://localhost:8000/admin` — логин из `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`,
по умолчанию `admin@dozvon.local` / `password`).

Роли: `admin` видит всё (включая Настройки/Тексты), `manager` — только операционные
ресурсы (пользователи, финансы, проверки, тикеты). Алерты админам в Telegram настраиваются
через `admin_chat_ids` (Настройки → список Telegram-ID).

### Бот

1. Укажите `TELEGRAM_TOKEN` в `.env`.
2. Локальная отладка (long-polling): `php artisan nutgram:run`.
3. Продакшн (webhook): `php artisan bot:webhook:set https://ваш-домен/<TOKEN>`.

### Фоновые процессы

```bash
php artisan queue:work       # обзвон, опрос результатов, генерация файлов
php artisan schedule:work    # payments:poll, premium:expire, checks:*
```

### Прод-интеграции

В `.env`: `ZVONOK_DRIVER=http` + `ZVONOK_API_KEY`/`ZVONOK_CAMPAIGN_ID`,
`USDT_PROVIDER=tronscan` + `USDT_WALLET`, `ZVONOK_POSTBACK_SECRET` (для webhook).
Точные эндпоинты/поля помечены `// TODO` в `HttpZvonokClient`/`TronscanWatcher`.

## Деплой одной командой (прод, radistka.pro за Cloudflare)

На чистом Ubuntu-сервере (нужен только Docker — скрипт поставит сам):

```bash
APP_DOMAIN=radistka.pro \
TELEGRAM_TOKEN=<токен_бота> \
TELEGRAM_BOT_USERNAME=<username_без_@> \
ADMIN_IDS=<ваш_telegram_id> \
MANAGER_URL=https://t.me/<менеджер> \
bash <(curl -fsSL https://raw.githubusercontent.com/Anz69/prozvonok-bot/main/deploy/install.sh)
```

Скрипт: ставит Docker → тянет код → генерит `.env` (APP_URL/MINIAPP_URL/секреты) →
собирает фронт (Vite во временном `node`-контейнере) → поднимает весь стек
(app + nginx + **caddy** + mysql + redis + queue + scheduler + bot) → миграции и сидеры →
сбрасывает webhook (бот на long-polling) → ставит кнопку-меню бота на Mini App.

**Cloudflare (обязательно 2 настройки для radistka.pro):**
1. **DNS** → A-запись `radistka.pro` → IP сервера, проксирование **включено** (оранжевое облако).
2. **SSL/TLS → Overview → режим `Full`** (не Flexible и не Full strict).
   Caddy на сервере отдаёт HTTPS с self-signed сертификатом (`tls internal`), Cloudflare
   его принимает в режиме Full и терминирует TLS для браузера. Никаких Let's Encrypt/ACME.

После деплоя:
- Mini App: `https://radistka.pro/app` (кнопка «Открыть приложение» в боте).
- Админка: `https://radistka.pro/admin` (логин/пароль — в `.env`, `ADMIN_*`).
- Постбэк Звонок.com: `POST https://radistka.pro/webhooks/zvonok/<ZVONOK_POSTBACK_SECRET>`.

> Порты 80/443 на сервере должны быть открыты для Cloudflare. Обновление —
> повторный запуск той же команды (код подтянется `git pull`, `.env` не перезапишется).

## Деплой (Docker, вручную / локально)

```bash
cp .env.example .env          # выставьте DB_*, REDIS_*, TELEGRAM_TOKEN, ключи интеграций
docker compose up -d --build  # app + nginx + caddy + mysql + redis + queue + scheduler
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Без домена (`APP_DOMAIN` не задан) приложение доступно на `http://localhost:8080`,
админка — `/admin`. Сервисы `queue` и `scheduler` поднимаются автоматически.

## Mini App (Telegram WebApp)

Веб-интерфейс бота (`/app`) на Vue 3 + Inertia + Tailwind v4 + GSAP: баланс, проверка базы,
пополнение, калькулятор, профиль, рефералы. Тёмная/светлая тема (синхрон с Telegram + тумблер).

Сборка фронтенда: `npm ci && npm run build` (для прод) или `npm run dev` (HMR). Telegram
требует **HTTPS** — его даёт Cloudflare, а `caddy` на origin отдаёт HTTPS с self-signed
(`tls internal`) при Cloudflare SSL = **Full**. Всё это делает `deploy/install.sh` (см. выше).
Кнопка-меню бота ставится командой `docker compose exec app php artisan bot:menu:set`.

**Локальный тест в браузере** (без Telegram): при `APP_ENV=local` вход в `/app` происходит
тест-юзером «Local Tester» — `php artisan serve` → открыть `http://localhost:8000/app`.

## Тесты

```bash
php artisan test
```

Покрыто ядро и интеграции: парсер номеров, расчёт стоимости, движения баланса, реферальные
начисления (вкл. +10% к первому пополнению), идемпотентность платежей, премиум, обработка
результатов обзвона и экспорт файла, доступ ролей в админке.
