# Дипломный проект

**Дипломный проект (ДП)** студента специальности **09.02.07** «Информационные системы и программирование», квалификация **специалист по информационным системам».

**Вид программного продукта:** CRM/ERP  
**Тема:** Агентство недвижимости  
**Студент, группа:** Галинов Иван Анатольевич, ИС-22-1

Веб-приложение для учёта объявлений, модерации, договоров и отчётности агентства недвижимости. Стек: **Laravel 12**, **PHP 8.2+**, **MySQL** (рекомендуется) / SQLite, фронтенд — **Vite**, **Tailwind CSS**, **Alpine.js**.

---

## Структура

Обязательная структура каталогов:

```
├── docs
│   ├── Презентация.pptx (при наличии)
│   ├── Презентация.pdf (формируется на предзащиту и защиту)
│   ├── Пояснительная записка.docx
│   ├── Пояснительная записка.pdf (формируется на предзащиту и защиту)
│   ├── Техническое задание.docx
│   ├── Техническое задание.pdf (формируется на предзащиту и защиту)
│   ├── Бланк-заказ.pdf (при наличии, сканированный документ с подписью)
│   ├── Бланк о внедрении.pdf (при наличии, сканированный документ с подписью)
│   ├── Задание на дипломное проектирование.pdf (сканированный документ с подписями)
│   ├── Отзыв руководителя.pdf (сканированный документ с подписью)
│   └── Рецензия.pdf (сканированный документ с подписью)
├── project
│    └── Исходные файлы проекта
└── README.md
```

В каталоге `project/` размещается исходный код приложения (Laravel): `app/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `artisan`, `composer.json` и др.

---

## Запуск проекта

### Требования

- PHP **8.2+** (расширения по требованиям Laravel: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo` и др.)
- **Composer** 2.x
- **Node.js** 18+ и **npm**
- **MySQL** 8.x / MariaDB (рекомендуется для защиты) или **SQLite** для локальной отладки

### Установка

Все команды ниже выполняются из каталога **`project/`**.

**Linux / macOS:**

```bash
cd project
composer install
cp .env.example .env
php artisan key:generate
```

**Windows (PowerShell):**

```powershell
cd project
composer install
copy .env.example .env
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

Для **SQLite** (по умолчанию в `.env.example`) создайте файл базы и примените миграции:

```bash
# Linux / macOS
touch database/database.sqlite

# Windows (PowerShell)
New-Item -ItemType File -Path database\database.sqlite -Force
```

Применение миграций и демо-данных (при необходимости):

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

Сервер разработки:

```bash
php artisan serve
```

Приложение будет доступно по адресу: **http://127.0.0.1:8000**

Режим разработки с hot-reload (сервер, очередь, логи, Vite):

```bash
composer run dev
```

Либо в двух терминалах: `php artisan serve` и `npm run dev`.

### Дополнительно

- Загрузка файлов (фото объектов): `php artisan storage:link`. Если симлинк на Windows недоступен, файлы отдаются через маршрут `/media/...`.
- Подсказки адресов **DaData** (опционально): в `.env` укажите `DADATA_API_KEY` и `DADATA_SECRET_KEY`.
- Роли в системе: **admin**, **realtor**, **client**. Учётную запись администратора можно создать через сидер (`php artisan db:seed`) или назначить роль в админ-панели после регистрации.

### Полезные команды

| Команда | Назначение |
|--------|------------|
| `php artisan migrate` | Применить миграции |
| `php artisan migrate:fresh --seed` | Пересоздать БД и заполнить сидерами |
| `php artisan storage:link` | Ссылка `public/storage` → `storage/app/public` |
| `composer run dev` | Сервер, очередь, логи и Vite одновременно |

---

## Docker (сервер)

Изолированный стек без конфликта с kanka (8082) и library (9080): контейнеры `galinov_*`, HTTP на **127.0.0.1:8083**.

```bash
cp .env.docker.example .env
# задайте APP_KEY, APP_URL, DB_PASSWORD, MYSQL_ROOT_PASSWORD
docker compose build
docker compose up -d
```


---

Проект учебный. Фреймворк Laravel распространяется под лицензией MIT.
