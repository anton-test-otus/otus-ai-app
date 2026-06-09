# Персональная база знаний

Многопользовательское веб-приложение для ведения персональной базы знаний с markdown-заметками, иерархическими папками, историей версий, wiki-ссылками, тегами и drag-and-drop организацией.

## Текущий статус

**Фаза 1: Основа - ✅ ЗАВЕРШЕНА**

Backend полностью функционален с JWT аутентификацией, API Platform, Doctrine ORM и всеми необходимыми сущностями.

## Стек технологий

### Backend
- PHP 8.3
- Symfony 7.4
- API Platform 4.3
- Doctrine ORM 3.6
- PostgreSQL 16
- JWT Authentication (LexikJWTAuthenticationBundle)
- Docker + Docker Compose

### Frontend (В разработке)
- Vue 3 + TypeScript
- Vite
- Pinia
- Tailwind CSS
- Milkdown (Markdown редактор)

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

4. API доступен на `http://localhost:8080/api` (или на порту, указанном в `APP_PORT`)
5. Swagger UI доступен на `http://localhost:8080/api/docs`

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
│   │   └── Repository/         # Doctrine репозитории
│   └── public/                 # Точка входа
├── docker/                     # Docker конфигурация
│   ├── nginx/                  # Nginx конфиг
│   └── php/                    # PHP Dockerfile
├── docker-compose.yml
├── ARCHITECTURE.md             # Архитектура приложения
├── PHASES.md                   # План реализации
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
make help          # Показать все доступные команды
make init          # Первоначальная настройка проекта
make build         # Сборка Docker образов
make up            # Запуск контейнеров
make down          # Остановка контейнеров
make restart       # Перезапуск контейнеров
make logs          # Просмотр логов (Ctrl+C для выхода)
make install       # Установка зависимостей Composer
make migrate       # Применение миграций
make admin         # Создание администратора из .env
make cache-clear   # Очистка кэша Symfony
make clean         # Удаление всех контейнеров и volumes
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

**Фаза 2: Основные функции заметок**
- [ ] Проект Vue 3 с Vite, TypeScript, Tailwind CSS
- [ ] Mobile-first адаптивная структура лейаута
- [ ] Pinia stores для авторизации и заметок
- [ ] Валидация на фронтенде с VeeValidate + Zod
- [ ] Интеграция редактора Milkdown
- [ ] Живой предпросмотр
- [ ] Автосохранение с уведомлением о статусе

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
