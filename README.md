# CRM «Галинов» — агентство недвижимости

Веб-приложение для каталога объявлений, модерации, договоров, CRM риэлтора и отчётности агентства недвижимости.

Стек: **Laravel 12**, **PHP 8.2+**, **MySQL** (рекомендуется) / SQLite, фронтенд — **Vite**, **Tailwind CSS**, **Alpine.js**.

---

## Запуск проекта

### Требования

- PHP **8.2+** (расширения по требованиям Laravel: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo` и др.)
- **Composer** 2.x
- **Node.js** 18+ и **npm**
- **MySQL** 8.x / MariaDB (рекомендуется) или **SQLite** для локальной отладки

### Установка

```bash
composer install
cp .env.example .env   # Windows: copy .env.example .env
php artisan key:generate
```

Настройте подключение к БД в файле `.env`. Пример для **MySQL**:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=имя_базы
DB_USERNAME=пользователь
DB_PASSWORD=пароль
```

Для **SQLite** создайте файл базы:

```bash
# Linux / macOS
touch database/database.sqlite

# Windows (PowerShell)
New-Item -ItemType File -Path database\database.sqlite -Force
```

Применение миграций и начальных данных (при необходимости):

```bash
php artisan migrate
php artisan db:seed
```

Сборка фронтенда:

```bash
npm install
npm run build
```

Полная установка одной командой (зависимости, `.env`, ключ, миграции, сборка):

```bash
composer run setup
```

### Запуск

```bash
php artisan serve
```

Приложение: **http://127.0.0.1:8000**

Режим разработки с hot-reload:

```bash
composer run dev
```

### Дополнительно

- Загрузка файлов: `php artisan storage:link`. На Windows, если симлинк недоступен, файлы отдаются через `/media/...`.
- Подсказки адресов **DaData** (опционально): `DADATA_API_KEY`, `DADATA_SECRET_KEY` в `.env`.
- Роли: **admin**, **realtor**, **client**. Тестовые учётные записи создаёт сидер (`php artisan migrate:fresh --seed`), пароль: **Password123!** (админ: `demo.admin@agency.local`).
- Почта и Telegram: `php artisan app:test-notifications agn@irk138.ru --telegram-chat=CHAT_ID`
- **Telegram с ПК:** `TELEGRAM_PROXY=socks5://…` в `.env`
- **Почта:** обычно работает на сервере `mail.irk138.ru` (5.35.125.156); с домашнего ПК TLS может отклоняться — тестируйте на VPS

### Полезные команды

| Команда | Назначение |
|--------|------------|
| `php artisan migrate` | Применить миграции |
| `php artisan migrate:fresh --seed` | Пересоздать БД и заполнить сидерами |
| `php artisan storage:link` | Ссылка `public/storage` → `storage/app/public` |
| `composer run dev` | Сервер, очередь, логи и Vite одновременно |

---

## Docker

```bash
cp .env.docker.example .env
docker compose build
docker compose up -d
```

Подробнее: [docker/README.md](docker/README.md).

---

Фреймворк Laravel распространяется под лицензией MIT.
