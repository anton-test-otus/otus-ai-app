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
├── REPORT.md                   # Отчёты о рефакторинге
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
make admin            # Создание администратора из .env
make cache-clear      # Очистка кэша Symfony
make frontend-install # Установка зависимостей npm (frontend)
make frontend-build   # Сборка production фронтенда
make clean            # Удаление всех контейнеров и volumes
```

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

# Создание администратора
docker exec otus_php bin/console app:create-admin

# Список роутов
docker exec otus_php bin/console debug:router

# Остановка контейнеров
docker compose down

# Пересборка контейнеров
docker compose build --no-cache && docker compose up -d
```

## Следующие шаги

**Фаза 3: Организация**
- [ ] Сущность папки и API
- [ ] Компонент дерева папок в боковой панели (адаптивный)
- [ ] Drag-and-drop для заметок и папок
- [ ] Реализация тегов
- [ ] Endpoint поиска заметок и UI

## Документация

- [ARCHITECTURE.md](./ARCHITECTURE.md) - Подробная архитектура приложения
- [PHASES.md](./PHASES.md) - План реализации по фазам
- [prompts.md](./prompts.md) - История разработки
- [REPORT.md](./REPORT.md) - Отчёты о рефакторинге

## Swagger UI

Интерактивная документация API доступна по адресу:
http://localhost:8080/api/docs

Здесь можно:
- Просмотреть все доступные endpoints
- Протестировать API запросы
- Посмотреть схемы данных
- Авторизоваться с JWT токеном

## Лицензия

Proprietary
