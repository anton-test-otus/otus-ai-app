# Персональная база знаний

Многопользовательское веб-приложение для ведения персональной базы знаний с markdown-заметками, иерархическими папками, историей версий, wiki-ссылками, тегами и drag-and-drop организацией.

## Текущий статус

**Фаза 1: Основа - ✅ ЗАВЕРШЕНА**
**Фаза 2: Основные функции заметок - ✅ ЗАВЕРШЕНА**

Backend полностью функционален с JWT аутентификацией, API Platform, Doctrine ORM.
Frontend реализован с Vue 3, аутентификацией, CRUD заметок, Markdown редактором и автосохранением.

## Стек технологий

### Backend
- PHP 8.3
- Symfony 7.4
- API Platform 4.3
- Doctrine ORM 3.6
- PostgreSQL 16
- JWT Authentication (LexikJWTAuthenticationBundle)
- Docker + Docker Compose

### Frontend
- Vue 3 + Composition API + TypeScript
- Vite 6
- Pinia (State Management)
- Vue Router 4
- PrimeVue 4 (UI Components)
- Tailwind CSS 3 (Styling)
- Milkdown 7.9 (Markdown WYSIWYG редактор)
- VeeValidate + Zod (Валидация форм)
- VueUse (Composables)
- Native Fetch API (HTTP Client - собственный wrapper)
- Marked (Markdown Parser)

## Быстрый старт

### Требования
- Docker и Docker Compose
- Make (опционально, но рекомендуется)
- Свободный порт для веб-сервера (по умолчанию 8080, настраивается через `APP_PORT` в `.env`)

### Первоначальная настройка

1. Клонируйте репозиторий и перейдите в директорию проекта

2. **Инициализация проекта (рекомендуемый способ):**
```bash
make init
```

Эта команда:
- Скопирует `backend/.env.example` в `backend/.env`
- Попросит вас отредактировать `.env` (установите безопасные пароли!)
- Соберёт Docker образы
- Запустит контейнеры
- Установит зависимости Composer
- Применит миграции базы данных
- Создаст администратора из переменных `ADMIN_EMAIL` и `ADMIN_PASSWORD`

3. **Важно!** Перед продолжением отредактируйте `backend/.env`:
```bash
# Измените значения на безопасные:
APP_SECRET=your_random_secret_min_32_chars
APP_PORT=8080  # Порт для доступа к приложению (8080 для разработки, 80 для продакшена)
DB_PASSWORD=your_secure_db_password
JWT_PASSPHRASE=your_jwt_passphrase_min_32_chars
ADMIN_EMAIL=your_admin@example.com
ADMIN_PASSWORD=your_secure_admin_password
```

**Примечание о портах:**
- По умолчанию приложение доступно на порту 8080 (удобно для разработки, не требует sudo)
- Для продакшена установите `APP_PORT=80` в `.env`
- Nginx внутри контейнера всегда слушает стандартный порт 80
- PostgreSQL работает только внутри Docker сети (порт не открыт наружу)

4. Доступ к приложению:
   - **Frontend**: http://localhost:5173
   - **Backend API**: http://localhost:8080/api
   - **Swagger UI**: http://localhost:8080/api/docs

### Prod / demo (без node, один URL)

```bash
cp .env.example .env                    # APP_AUTH_ENABLED=false для single-user
cp backend/.env.example backend/.env    # DB, APP_SECRET, JWT_PASSPHRASE

make init-prod
# или:
make frontend-build                      # npm ci + vite build → frontend/dist
docker compose build && docker compose up -d
```

- Фронт собирается **до** образа nginx (`make frontend-build` или job CI); `VITE_AUTH_ENABLED` берётся из корневого `.env` (`APP_AUTH_ENABLED`) — **build-time**
- Образ nginx копирует готовый `frontend/dist` (без node-stage в Dockerfile)
- Бэкенд: migrate + `app:ensure-single-user` в entrypoint php — **runtime**
- Приложение: http://localhost:8080/ (`/` → SPA, `/api` → Symfony)

**Смена `APP_AUTH_ENABLED`** → `make frontend-build && docker compose build nginx`.

### Dev (Vite)

```bash
make init-dev
# docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

- API: http://localhost:8080/api · UI: http://localhost:5173

### Однопользовательский режим — один параметр

В корневом `.env`: `APP_AUTH_ENABLED=false`. Compose прокидывает его в php (runtime); для prod-сборки фронта — `make frontend-build` (читает `APP_AUTH_ENABLED` → `VITE_AUTH_ENABLED`). Дополнительно UI определяет режим по `GET /api/auth/me` без токена.

### Альтернативный способ (без Make)

```bash
# 1. Скопируйте .env.example
cp backend/.env.example backend/.env

# 2. Отредактируйте backend/.env (установите безопасные значения)

# 3. Соберите образы
docker compose build

# 4. Запустите контейнеры
docker compose up -d

# 5. Установите зависимости
docker exec otus_php composer install

# 6. Примените миграции
docker exec otus_php bin/console doctrine:migrations:migrate --no-interaction

# 7. Создайте администратора
docker exec otus_php bin/console app:create-admin
```

### Примеры использования API

#### Регистрация
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password123"}'
```

#### Вход (получение JWT токена)
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin@test.com", "password": "admin123"}'
```

#### Получение информации о текущем пользователе
```bash
curl http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Список заметок
```bash
curl http://localhost:8080/api/notes \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

#### Создание заметки
```bash
curl -X POST http://localhost:8080/api/notes \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Моя первая заметка",
    "content": "# Заголовок\n\nСодержимое заметки в markdown"
  }'
```

## Структура проекта

```
otus-ai-app/
├── backend/                    # Symfony приложение
│   ├── config/                 # Конфигурация
│   ├── migrations/             # Миграции БД
│   ├── src/
│   │   ├── Controller/         # REST контроллеры
│   │   ├── Entity/             # Doctrine сущности
│   │   ├── Repository/         # Doctrine репозитории
│   │   └── Command/            # Консольные команды
│   ├── tests/                  # PHPUnit (Functional, Unit, Integration)
│   └── public/                 # Точка входа
├── frontend/                   # Vue 3 приложение
│   ├── src/
│   │   ├── api/                # API клиенты
│   │   ├── components/         # Vue компоненты
│   │   ├── composables/        # Composables
│   │   ├── router/             # Vue Router
│   │   ├── stores/             # Pinia stores
│   │   ├── types/              # TypeScript типы
│   │   └── views/              # Страницы
│   ├── package.json
│   └── vite.config.ts
├── docker/                     # Docker конфигурация
│   ├── nginx/                  # Nginx конфиг
│   └── php/                    # PHP Dockerfile
├── docker-compose.yml
├── Makefile
├── ARCHITECTURE.md             # Архитектура приложения
├── PHASES.md                   # План реализации
├── demoseed.md                 # Спецификация demo seed (фаза 15)
├── REPORT.md                   # Проблемы рефакторинга и их решения
└── prompts.md                  # История разработки
```

## API Endpoints

### Аутентификация
- `POST /api/auth/register` - Регистрация
- `POST /api/auth/login` - Вход (получение JWT)
- `GET /api/auth/me` - Текущий пользователь

### Заметки
- `GET /api/notes` - Список заметок (пагинация)
- `GET /api/notes/{id}` - Получение заметки
- `POST /api/notes` - Создание заметки
- `PUT /api/notes/{id}` - Обновление заметки
- `DELETE /api/notes/{id}` - Удаление заметки

### Папки
- `GET /api/folders` - Список папок
- `POST /api/folders` - Создание папки
- `PUT /api/folders/{id}` - Обновление папки
- `DELETE /api/folders/{id}` - Удаление папки

### Теги
- `GET /api/tags` - Список тегов
- `POST /api/tags` - Создание тега
- `DELETE /api/tags/{id}` - Удаление тега

### Администрирование (требуется ROLE_ADMIN)
- `GET /api/admin/users` - Список пользователей
- `GET /api/admin/users/{id}` - Информация о пользователе
- `PATCH /api/admin/users/{id}/enable` - Активация пользователя
- `PATCH /api/admin/users/{id}/disable` - Деактивация пользователя
- `PATCH /api/admin/users/{id}/promote` - Назначить роль администратора
- `PATCH /api/admin/users/{id}/demote` - Снять роль администратора
- `DELETE /api/admin/users/{id}` - Удаление пользователя

## База данных

PostgreSQL работает внутри Docker сети и не открыт наружу для повышения безопасности.

Для прямого доступа к БД используйте:
```bash
# Подключение через psql
docker exec -it otus_postgres psql -U otus_user -d otus_ai_db

# Или через командную строку
docker exec -it otus_postgres psql -U otus_user -d otus_ai_db -c "SELECT * FROM users;"
```

### Сущности
- **User** - Пользователи (с ролями ROLE_USER, ROLE_ADMIN)
- **Note** - Заметки (с мягким удалением)
- **Folder** - Папки (иерархические)
- **Tag** - Теги
- **NoteVersion** - Версии заметок
- **NoteLink** - Wiki-ссылки между заметками

Администратор создаётся автоматически при выполнении `make init` или `make admin` на основе переменных `ADMIN_EMAIL` и `ADMIN_PASSWORD` из `.env` файла.

Для назначения роли администратора существующему пользователю используйте endpoint `PATCH /api/admin/users/{id}/promote`.

## Разработка

### Makefile команды

Проект использует Makefile для упрощения разработки:

```bash
make help             # Показать все доступные команды
make init             # Первоначальная настройка проекта
make build            # Сборка Docker образов
make up               # Запуск контейнеров
make down             # Остановка контейнеров
make restart          # Перезапуск контейнеров
make logs             # Просмотр логов (Ctrl+C для выхода)
make install          # Установка зависимостей Composer (backend)
make migrate          # Применение миграций
make db-test          # Создание test БД для PHPUnit
make schema-reset     # Очистка схемы БД и повторное применение миграций
make admin            # Создание администратора из .env
make cache-clear      # Очистка кэша Symfony
make test             # PHPUnit (backend)
make frontend-test    # Vitest (frontend)
make frontend-install # Установка зависимостей npm (frontend)
make frontend-build   # Сборка production фронтенда
make clean            # Удаление всех контейнеров и volumes
```

### Frontend: `node_modules` и IDE

npm-пакеты фронтенда хранятся в **`volumes/node_modules`** (Docker volume), а не в `frontend/node_modules`. В контейнере `node` путь `/app/node_modules` указывает на volume; на хосте `frontend/node_modules` при этом остаётся пустым.

- Сборка и typecheck: `make frontend-build` (prod) или `docker compose exec node npm run build` (dev)
- Установка пакетов: `docker compose exec node npm install` (не на хосте в `frontend/`)

**Для Cursor / VS Code** — symlink, чтобы IDE резолвила типы (после первого `make up`):

```bash
rm -rf frontend/node_modules
ln -s ../volumes/node_modules frontend/node_modules
```

Затем перезапустить TypeScript Server в IDE. Подробнее: [`frontend/README.md`](./frontend/README.md), правило `.cursor/rules/docker-packages.mdc`.

### Прямые команды Docker (без Make)

```bash
# Просмотр логов
docker compose logs -f

# Очистка кэша Symfony
docker exec otus_php bin/console cache:clear

# Создание миграции
docker exec otus_php bin/console doctrine:migrations:diff

# Применение миграций
docker exec otus_php bin/console doctrine:migrations:migrate

# Очистка схемы и повторное применение миграций (после изменения начальной миграции)
docker exec otus_php bin/console app:reset-schema

# Только удалить схему, без миграций
docker exec otus_php bin/console app:reset-schema --no-migrate

# Создание администратора
docker exec otus_php bin/console app:create-admin

# Загрузка demo-данных (3 вселенные, пароль demo1234)
docker exec otus_php bin/console app:seed-demo-data

# Пересоздать demo-данные (удалить существующих demo-пользователей)
docker exec otus_php bin/console app:seed-demo-data --force

# Список роутов
docker exec otus_php bin/console debug:router

# Остановка контейнеров
docker compose down

# Пересборка контейнеров
docker compose build --no-cache && docker compose up -d
```

## Тесты

Автотесты реализованы в ветках `test/*` (см. [`autotests_prs.md`](./autotests_prs.md)). Спецификации кейсов — в [`future_autotests.md`](./future_autotests.md).

**Backend:** PHPUnit (`symfony/test-pack`), functional/unit/integration-тесты в `backend/tests/`.  
**Frontend:** Vitest + happy-dom, unit/component-тесты в `frontend/src/**/*.test.ts`.

Запуск **только через Docker** (как и установка зависимостей):

```bash
# Контейнеры должны быть запущены
docker compose up -d
```

### Первый запуск (test database)

PHPUnit использует отдельную БД с суффиксом `_test` (`otus_ai_db_test`). Создайте её один раз:

```bash
make db-test
```

Functional-тесты сами пересоздают схему в `setUp` (`ApiTestCase::resetDatabase`); миграции для прогона тестов не нужны.

### Все тесты

```bash
# Backend (~70 тестов)
docker compose exec php php bin/phpunit

# Frontend (~49 тестов)
make frontend-test
# или: docker compose exec node npm test
```

Краткий вариант для backend (`make test` = `db-test` + PHPUnit):

```bash
make test
```

### Отдельные файлы или наборы

```bash
# Backend: один класс
docker compose exec php php bin/phpunit tests/Functional/ResourceOwnershipTest.php

# Backend: несколько файлов
docker compose exec php php bin/phpunit tests/Functional/JwtRefreshTest.php tests/Functional/AuthRegisterTest.php

# Frontend: один файл
docker compose exec node npm test -- src/stores/__tests__/notes.store.test.ts

# Frontend: watch-режим (разработка)
docker compose exec node npm run test:watch
```

### Конфигурация

| Компонент | Файл |
|-----------|------|
| PHPUnit | `backend/phpunit.dist.xml` |
| Test env | `backend/.env.test`, `backend/tests/bootstrap.php` |
| Vitest | `frontend/vitest.config.ts` |

Переменная `APP_ENV=test` задаётся в `tests/bootstrap.php` и `phpunit.dist.xml`. Не запускайте `phpunit` / `npm test` на хосте в `backend/` и `frontend/` — окружение и `node_modules` рассчитаны на контейнеры `php` и `node`.

## Demo-данные

Для dev/demo и проверки графа связей, тегов и версий:

```bash
docker compose exec php php bin/console app:seed-demo-data
```

| Email | Пароль | Вселенная |
|-------|--------|-----------|
| `hogwarts@demo.local` | `demo1234` | Гарри Поттер (~40 заметок) |
| `westeros@demo.local` | `demo1234` | Игра престолов (~47 заметок) |
| `witcher@demo.local` | `demo1234` | Ведьмак (~39 заметок) |

Флаг `--force` удаляет существующих demo-пользователей и загружает данные заново. Администратор создаётся отдельно: `app:create-admin` (из `ADMIN_EMAIL` / `ADMIN_PASSWORD` в `.env`).

Спецификация: [`demoseed.md`](./demoseed.md).

## Документация

- [ARCHITECTURE.md](./ARCHITECTURE.md) - Подробная архитектура приложения
- [PHASES.md](./PHASES.md) - План реализации по фазам
- [prompts.md](./prompts.md) - История разработки
- [REPORT.md](./REPORT.md) - Проблемы рефакторинга и их решения
- [demoseed.md](./demoseed.md) - Спецификация demo seed
- [future_autotests.md](./future_autotests.md) - Спецификации автотестов
- [autotests_prs.md](./autotests_prs.md) - План PR/веток для автотестов

## Swagger UI

Интерактивная документация API (API Platform + OpenAPI 3):

| Ресурс | URL |
|--------|-----|
| **Swagger UI** (браузер) | http://localhost:8080/api/docs |
| **OpenAPI JSON** | http://localhost:8080/api/docs.jsonopenapi |
| **OpenAPI YAML** | http://localhost:8080/api/docs.yamlopenapi |

> Порт `8080` — значение по умолчанию (`APP_PORT` в `.env`). Для продакшена подставьте свой хост и порт.

В Swagger UI можно:
- просмотреть endpoints API Platform (заметки, папки, теги, версии);
- протестировать запросы прямо из браузера;
- посмотреть схемы данных и форматы ответов.

**Авторизация в Swagger UI:**
1. Выполните `POST /api/auth/login` с `username` (email) и `password`.
2. Скопируйте значение `token` из ответа.
3. Нажмите **Authorize** → введите `Bearer <token>` (префикс `Bearer` обязателен).

В Swagger также описаны кастомные endpoints: **Auth** (`/api/auth/*`), **Admin** (`/api/admin/*`, требуется `ROLE_ADMIN`) и **WikiLinks**.

## Лицензия

Proprietary
