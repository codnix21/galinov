# Развёртывание (Docker)

Изолированный стек: сеть `galinov_net`, контейнеры `galinov_app`, `galinov_db`, `galinov_scheduler`, HTTP **127.0.0.1:8083** (nginx и PHP-FPM в одном контейнере `app`).

## Требования

- Docker и Docker Compose v2
- Git

## Запуск

```bash
cp .env.docker.example .env
# APP_KEY, APP_URL, DB_PASSWORD, MYSQL_ROOT_PASSWORD
# Опционально: YANDEX_MAPS_API_KEY, TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_USERNAME

docker compose build
docker compose up -d

docker compose exec app php artisan storage:link
docker compose exec app php artisan db:seed --class=DemoDataSeeder
```

Проверка: `curl -I http://127.0.0.1:8083`

Демо-фото уже в `database/seeders/media/`. Пароль демо: `Password123!`, админ: `demo.admin@agency.local`

## Демо-данные (важно: только внутри контейнера)

На VPS **не запускайте** `php artisan` в `/opt/galinov` с хоста — там нет папки `vendor`. Все команды — через Docker:

```bash
cd /opt/galinov
docker compose up -d
```

**Первый запуск** (после миграций):

```bash
docker compose exec app php artisan db:seed --class=DemoDataSeeder
```

**Сообщение «Демо-данные уже загружены»** — в БД уже есть демо. Варианты:

1. **Только перезалить фото** (объявления и пользователи останутся, обновятся документы/заявки из сидера):

```bash
docker compose exec -e DEMO_RESEED_MEDIA=1 app php artisan db:seed --class=DemoDataSeeder
```

2. **Полностью с нуля** (⚠️ удалит все таблицы и данные):

```bash
docker compose exec app php artisan migrate:fresh --seed --force
```

Флаг `--force` нужен в production (`APP_ENV=production`).

**Проверка:**

```bash
docker compose exec app php artisan tinker --execute="echo 'properties=' . \App\Models\Property::count() . PHP_EOL;"
```

## Прокси Nginx на хосте

```nginx
location / {
    proxy_pass http://127.0.0.1:8083;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## Telegram

1. Создайте бота через [@BotFather](https://t.me/BotFather), укажите `TELEGRAM_BOT_TOKEN` и `TELEGRAM_BOT_USERNAME` в `.env`.
2. Webhook (HTTPS): `https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://ваш-домен/telegram/webhook&secret_token=<TELEGRAM_WEBHOOK_SECRET>`
3. В профиле на сайте (риэлтор/админ) — «Сгенерировать ссылку для бота» или вручную Chat ID.

## Напоминания

Контейнер `galinov_scheduler` запускает `php artisan schedule:work` (09:00 и 18:00): просроченные договоры, окончание аренды, задачи, показы.

Ручной запуск: `docker compose exec app php artisan app:send-reminders`

## Карта

Каталог → **Карта** (`/properties/map`). Нужен ключ Яндекс.Карт в `.env`.

## Обновление

```bash
git pull
docker compose build app
docker compose up -d
docker compose exec app php artisan migrate --force
```

## Сборка: ошибки apk / DNS

Если при `docker compose build` видите:

`DNS: transient error` или `no such package: autoconf/gcc` при `docker-php-ext-install`

1. **Повторите сборку** (часто временный сбой DNS на VPS):
   ```bash
   docker compose build --no-cache app
   ```

2. **Проверьте DNS на сервере:**
   ```bash
   ping -c 2 dl-cdn.alpinelinux.org
   ```

3. **DNS для Docker** (если сбои повторяются), в `/etc/docker/daemon.json`:
   ```json
   {
     "dns": ["8.8.8.8", "1.1.1.1"]
   }
   ```
   Затем: `systemctl restart docker`

4. Обновите репозиторий на сервере (`git pull`) — stage `composer_vendor` использует образ `composer:2` без сборки zip через apk.
