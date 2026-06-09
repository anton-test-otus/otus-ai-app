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
- Порты 8080 (nginx) и 5432 (postgres) должны быть свободны

### Запуск

1. Клонируйте репозиторий и перейдите в директорию проекта

2. Запустите Docker контейнеры:
```bash
docker compose up -d
```

3. Проверьте статус:
```bash
docker compose ps
```

4. API доступен на `http://localhost:8080/api`
5. Swagger UI доступен на `http://localhost:8080/api/docs`

### Тестовый пользователь

Создан администратор для тестирования:
- **Email:** admin@test.com
- **Пароль:** admin123

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
- `DELETE /api/admin/users/{id}` - Удаление пользователя

## База данных

### Сущности
- **User** - Пользователи (с ролями ROLE_USER, ROLE_ADMIN)
- **Note** - Заметки (с мягким удалением)
- **Folder** - Папки (иерархические)
- **Tag** - Теги
- **NoteVersion** - Версии заметок
- **NoteLink** - Wiki-ссылки между заметками

Первый зарегистрированный пользователь автоматически получает роль ROLE_ADMIN.

## Разработка

### Полезные команды

```bash
# Просмотр логов
docker compose logs -f

# Очистка кэша Symfony
docker exec otus_php bin/console cache:clear

# Создание миграции
docker exec otus_php bin/console doctrine:migrations:diff

# Применение миграций
docker exec otus_php bin/console doctrine:migrations:migrate

# Список роутов
docker exec otus_php bin/console debug:router

# Остановка контейнеров
docker compose down

# Пересборка контейнеров
docker compose up -d --build
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
