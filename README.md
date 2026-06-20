# Персональная база знаний

[![CI](https://github.com/anton-test-otus/otus-ai-app/actions/workflows/ci.yml/badge.svg)](https://github.com/anton-test-otus/otus-ai-app/actions/workflows/ci.yml)

Многопользовательское веб-приложение для ведения персональной базы знаний с markdown-заметками, иерархическими папками, историей версий, wiki-ссылками, тегами и drag-and-drop организацией.

## Текущий статус

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
- Для параллельных инстансов — уникальные `APP_NAME`, `APP_PORT` и при необходимости `FRONTEND_PORT` / `DB_NAME` (см. `ARCHITECTURE.md`)

### Режим окружения (`DOCKER_ENV`)

В корневом `.env`:

| `DOCKER_ENV` | Назначение | URL |
|--------------|------------|-----|
| **`demo`** (по умолчанию) | Статический SPA + API на одном порту (сдача, демо) | http://localhost:8080/ |
| **`dev`** | Vite hot reload, mount backend | API http://localhost:8080/api · UI http://localhost:5173 |

Одна команда инициализации — режим берётся из `.env`:

```bash
cp .env.example .env
# demo по умолчанию; для разработки: DOCKER_ENV=dev
# отредактируйте секреты, пароли
make init
```

Дальнейшие команды (`make build`, `make up`, `make down`, …) используют тот же `DOCKER_ENV`. Разовый override: `make up DOCKER_ENV=demo`.

**Что делает Makefile автоматически** (ручные шаги ниже не нужны):
- `make env` — генерация `backend/.env` и `frontend/.env` из корневого `.env` (вызывается из `init`, `build`, `up`)
- `make volumes-init` — каталоги `volumes/${APP_NAME}/…` (вызывается из `init`, `up`)
- demo: `make frontend-dist` — подтягивание `frontend/dist` с ветки `dist` или локальная сборка

### Demo (`DOCKER_ENV=demo`)

Приложение: **http://localhost:8080/** — SPA и API на одном порту, без Vite dev server.

При `APP_AUTH_ENABLED=true` (по умолчанию) demo-данные **не** загружаются при развёртывании — только явной командой: `make seed-demo-if-missing` (см. раздел «Demo-данные»).

| Email | Пароль | Вселенная |
|-------|--------|-----------|
| `hogwarts@demo.local` | `demo1234` | Гарри Поттер (~40 заметок) |
| `westeros@demo.local` | `demo1234` | Игра престолов (~47 заметок) |
| `witcher@demo.local` | `demo1234` | Ведьмак (~39 заметок) |

Администратор (опционально): `app:create-admin` — `ADMIN_EMAIL` / `ADMIN_PASSWORD` в корневом `.env`.

#### Способ 1 — docker compose (без Make)

```bash
# 1. Env (DOCKER_ENV=demo в .env)
cp .env.example .env
# Отредактируйте .env: секреты, DB; для demo — VITE_API_URL=/api
chmod +x scripts/generate-env.sh
./scripts/generate-env.sh
# JWT keys для demo не нужны на хосте — создаются в образе php при build и при старте контейнера

# 2. Frontend-артефакты (frontend/dist) — нужны ДО сборки nginx
git fetch origin dist
git restore --source=origin/dist --worktree -- frontend/dist .dist-source-sha

# 3. Образы и контейнеры (каталог postgres создаётся при первом up)
docker compose build
docker compose up -d

# 4. Дождаться bootstrap (migrate) — ~10 с
docker compose logs php | tail -20

# 5. (Опционально) администратор
docker compose exec php bin/console app:create-admin
```

**Проверка:** откройте http://localhost:8080/ (после `make seed-demo-if-missing` — вход как `hogwarts@demo.local` / `demo1234`).

**Что происходит при `docker compose up` (demo):**
- **nginx** — раздаёт `frontend/dist` и проксирует `/api` в Symfony
- **php (entrypoint)** — миграции; при `APP_AUTH_ENABLED=false` — `app:ensure-single-user`
- **postgres** — БД в `volumes/${APP_NAME}/postgres/data` (`.gitkeep` — в родительском `postgres/`)

**Смена `APP_AUTH_ENABLED`** → пересобрать `frontend/dist` и `docker compose build nginx`.

#### Способ 2 — Make

```bash
cp .env.example .env
# DOCKER_ENV=demo в .env
make init   # env + frontend/dist + build + up + migrate (+ admin в dev)
# опционально: make admin, make seed-demo-if-missing
```

`make init` при `DOCKER_ENV=demo` = `env` + `frontend-dist` + `docker compose build` + `up` + migrate (entrypoint). Demo seed — отдельно: `make seed-demo-if-missing`.

#### Артефакты frontend (CI / ветка `dist`)

На `main` каталог `frontend/dist` в `.gitignore`. CI ([`.github/workflows/build.yml`](.github/workflows/build.yml)) после успешного CI собирает dist и пушит в ветку **`dist`**.

Локально dist подтягивается через `git restore --worktree` (только рабочая копия, без изменений в `git status` на `main`).

Деплой с готовыми ассетами (без `npm` на сервере):

```bash
git pull origin main
cp .env.example .env   # DOCKER_ENV=demo, секреты
make env
make frontend-dist
docker compose build && docker compose up -d
# опционально: make seed-demo-if-missing
```

### Dev (`DOCKER_ENV=dev`)

```bash
cp .env.example .env
# В .env: DOCKER_ENV=dev и секреты (см. ниже)
make init   # пауза для правки .env → build, up, composer, migrate, admin
```

Минимум для правки в корневом `.env` перед Enter в `make init`:

```bash
DOCKER_ENV=dev
APP_SECRET=your_random_secret_min_32_chars
DB_PASSWORD=your_secure_db_password
JWT_PASSPHRASE=your_jwt_passphrase_min_32_chars
ADMIN_EMAIL=your_admin@example.com
ADMIN_PASSWORD=your_secure_admin_password
VITE_API_URL=http://localhost:8080/api   # demo: /api
```

После правок `make build` / `make up` сами перегенерируют `backend/.env` и `frontend/.env` — отдельный `./scripts/generate-env.sh` не нужен.

- API: http://localhost:8080/api · UI: http://localhost:5173 · Swagger: http://localhost:8080/api/docs
- Demo seed (опционально): `make seed-demo-if-missing`; пересоздать — `make seed-demo`
- Альтернатива без паузы: `make build && make up && make install && make migrate && make admin`

**Порты:** по умолчанию API на 8080; для продакшена — `APP_PORT=80`. PostgreSQL только внутри Docker-сети.

### Однопользовательский режим — один параметр

В корневом `.env`: `APP_AUTH_ENABLED=false`. Compose прокидывает его в php (runtime); для demo-сборки фронта — пересобрать `frontend/dist` с `VITE_AUTH_ENABLED=false`. Дополнительно UI определяет режим по `GET /api/auth/me` без токена.

### Dev без Make (docker compose)

```bash
cp .env.example .env   # DOCKER_ENV=dev
make env

docker compose -f docker-compose.yml -f docker-compose.dev.yml build
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
# node сам выполняет npm install при старте; entrypoint php — migrate (+ ensure-single-user при APP_AUTH_ENABLED=false)

docker compose exec php composer install --no-interaction
docker compose exec php bin/console app:create-admin
# опционально: make seed-demo-if-missing
```

### CI: сборка артефактов

Workflow [`.github/workflows/build.yml`](.github/workflows/build.yml) запускается **после успешного CI** на `main` (и вручную через **Actions → Build artifacts**):

1. **`frontend`** — `npm ci` + `vite build` → artifact **`frontend-dist`** (30 дней)
2. **`publish-dist`** — push в ветку **`dist`** с `frontend/dist` + `.dist-source-sha`

Сервис **cron** в CI/CD отдельно не собирается: это тот же backend-образ с другим `command` (`crond`). Логика `app:cleanup-trash` проверяется PHPUnit (`CleanupTrashCommandTest`), не контейнером cron.

Тесты на PR/push — [`.github/workflows/ci.yml`](.github/workflows/ci.yml): PHPUnit, Vitest, `vue-tsc` (через `npm run build`), проверка сборки nginx. ESLint не подключён — typecheck через `vue-tsc`.

**Настройка GitHub (один раз):** Settings → Actions → General → Workflow permissions → **Read and write permissions**. PR не создаётся — прямой push в `dist` (не требует «Allow GitHub Actions to create pull requests»).

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
make help             # Показать все доступные команды (текущий DOCKER_ENV)
make init             # Первоначальная настройка (режим из DOCKER_ENV в .env)
make build            # Сборка Docker образов
make up               # Запуск контейнеров
make down             # Остановка контейнеров
make restart          # Перезапуск контейнеров
make logs             # Просмотр логов (Ctrl+C для выхода)
make install          # Установка зависимостей Composer (backend)
make migrate          # Применение миграций
make db-test          # Создание test БД для PHPUnit
make schema-reset     # Очистка схемы БД и повторное применение миграций
make seed-demo-if-missing  # Demo seed, если пользователей ещё нет
make seed-demo        # Пересоздать demo-данные (--force)
make admin            # Создание администратора из .env
make cache-clear      # Очистка кэша Symfony
make test             # PHPUnit (backend)
make frontend-test    # Vitest (frontend)
make frontend-install # Установка зависимостей npm (frontend)
make frontend-build   # Сборка production фронтенда
make clean            # Полная очистка: Docker, volumes и артефакты сборки
make clean-artifacts  # Только артефакты сборки (dist, JWT keys, vendor, var...)
```

### Frontend: `node_modules` и IDE

npm-пакеты фронтенда хранятся в **`volumes/${APP_NAME}/node_modules`** (по умолчанию `volumes/otus_ai/node_modules`, Docker volume), а не в `frontend/node_modules`. В контейнере `node` путь `/app/node_modules` указывает на volume; на хосте `frontend/node_modules` при этом остаётся пустым.

- Сборка и typecheck: `make frontend-build` (prod) или `docker compose exec node npm run build` (dev)
- Установка пакетов: `docker compose exec node npm install` (не на хосте в `frontend/`)

**Для Cursor / VS Code** — symlink, чтобы IDE резолвила типы (после первого `make up`):

```bash
rm -rf frontend/node_modules
ln -s ../volumes/otus_ai/node_modules frontend/node_modules
```

Затем перезапустить TypeScript Server в IDE. Подробнее: [`frontend/README.md`](./frontend/README.md), правило `.cursor/rules/docker-packages.mdc`.

### Прямые команды Docker (без Make)

```bash
# Просмотр логов
docker compose logs -f

# Очистка кэша Symfony
docker compose exec php bin/console cache:clear

# Создание миграции
docker compose exec php bin/console doctrine:migrations:diff

# Применение миграций
docker compose exec php bin/console doctrine:migrations:migrate

# Очистка схемы и повторное применение миграций (после изменения начальной миграции)
docker compose exec php bin/console app:reset-schema

# Только удалить схему, без миграций
docker compose exec php bin/console app:reset-schema --no-migrate

# Создание администратора
docker compose exec php bin/console app:create-admin

# Загрузка demo-данных (3 вселенные, пароль demo1234)
docker compose exec php bin/console app:seed-demo-data

# Пересоздать demo-данные (удалить существующих demo-пользователей)
docker compose exec php bin/console app:seed-demo-data --force

# Список роутов
docker compose exec php bin/console debug:router

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

При `APP_AUTH_ENABLED=true` demo-данные **не** загружаются при `docker compose up` — явно:

```bash
make seed-demo-if-missing
# или: docker compose exec php bin/console app:seed-demo-data --if-missing
```

Пересоздать принудительно:

```bash
make seed-demo
# или: docker compose exec php bin/console app:seed-demo-data --force
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
