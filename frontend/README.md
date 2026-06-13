# Frontend - Персональная база знаний

Vue 3 фронтенд приложения для ведения персональной базы знаний.

## Технологии

- **Vue 3** - фреймворк
- **TypeScript** - типобезопасность
- **Vite** - сборщик и dev server
- **Pinia** - управление состоянием
- **Vue Router** - маршрутизация
- **PrimeVue** - UI компоненты
- **Tailwind CSS** - утилитарные стили
- **Milkdown** - Markdown WYSIWYG редактор
- **VeeValidate + Zod** - валидация форм
- **VueUse** - composables (автосохранение, debounce)
- **Native Fetch API** - HTTP клиент (собственный wrapper, 0 зависимостей)

## Структура проекта

```
src/
├── api/              # API клиенты
│   ├── client.ts     # Базовый HTTP клиент
│   ├── auth.ts       # Аутентификация
│   └── notes.ts      # Заметки
├── components/
│   ├── common/       # Общие компоненты
│   ├── editor/       # Markdown редактор и превью
│   └── layout/       # Layout компоненты
├── composables/      # Vue composables
│   ├── useAutosave.ts
│   └── useTheme.ts
├── router/           # Маршрутизация
├── stores/           # Pinia stores
│   ├── auth.ts
│   └── notes.ts
├── styles/           # Глобальные стили
├── types/            # TypeScript типы
├── views/            # Страницы приложения
├── App.vue
└── main.ts
```

## Запуск в Docker (рекомендуется)

Из корня проекта:

```bash
make up          # Запуск всех контейнеров (включая Node.js)
make logs        # Просмотр логов
```

Frontend будет доступен на http://localhost:5173

### `node_modules` в volume

npm-зависимости **не** лежат в `frontend/node_modules` на диске. Они в `volumes/node_modules` и монтируются в контейнер как `/app/node_modules`.

- Установка пакетов: `docker compose exec node npm install` (из корня проекта)
- Production-сборка: `docker compose exec node npm run build`
- **Не** запускай `npm install` в `frontend/` на хосте

### IDE / TypeScript (Cursor, VS Code)

На хосте `frontend/node_modules` пустой — IDE не видит типы из `@milkdown/*`, `@types/*` и показывает ложные ошибки (`implicit any`), хотя `vue-tsc` в Docker проходит.

Один раз после первого `docker compose up`:

```bash
# из корня репозитория
rm -rf frontend/node_modules
ln -s ../volumes/node_modules frontend/node_modules
```

Перезапусти TS Server в IDE. Symlink не коммитится ( `frontend/node_modules` в `.gitignore`).

## Локальная разработка без Docker

Не рекомендуется для этого проекта. Если всё же нужно — `npm install` создаст **отдельный** `node_modules` на хосте, несовместимый с Docker-volume. Для штатной работы используй Docker + symlink выше.

```bash
cd frontend
npm install   # только вне Docker-схемы проекта
npm run dev
```

## Сборка production

В Docker (рекомендуется):

```bash
docker compose exec node npm run build
```

Собранные файлы: `frontend/dist/`.

## Доступные команды

- `npm run dev` - запуск dev server
- `npm run build` - production сборка
- `npm run preview` - просмотр production сборки

## Особенности реализации

### Адаптивный дизайн (Mobile-First)

- **Мобильные** (< 640px): одноколоночный layout, упрощенная навигация
- **Планшеты** (≥ 768px): двухколоночный layout
- **Десктопы** (≥ 1024px): полный функционал, split-view редактор

### Автосохранение

- Debounce: 2 секунды
- Индикаторы: иконка для мобильных, текст + иконка для десктопа
- Статусы: idle, saving, saved, error

### Режимы редактора

- **Edit** - только редактор
- **Split** - редактор + превью (по умолчанию на десктопе)
- **Preview** - только превью

### Темы

- Светлая и темная тема
- Автоматическое определение системных настроек
- Сохранение выбора в localStorage

## Конфигурация

### Переменные окружения

В Docker окружении переменные настраиваются через `docker-compose.yml`:

- `VITE_API_URL` - URL бэкенд API (по умолчанию: http://localhost:8080/api)

### Proxy в Vite

API запросы к `/api/*` автоматически проксируются на бэкенд nginx контейнер.

## API интеграция

### Аутентификация

JWT токены хранятся в localStorage и автоматически добавляются к каждому запросу через axios interceptor.

### Обработка ошибок

- 401 (Unauthorized) - автоматический редирект на `/login`
- Другие ошибки - показываются через toast уведомления

## Фаза 2 - Реализованные функции

✅ Проект Vue 3 с Vite, TypeScript, Tailwind CSS
✅ Mobile-first адаптивный layout
✅ Pinia stores для auth и notes
✅ Валидация форм с VeeValidate + Zod
✅ Интеграция Milkdown редактора
✅ Живой preview markdown
✅ Автосохранение с индикатором статуса
✅ Темная и светлая темы
✅ Страницы: Login, Register, Dashboard, Note

## TODO (следующие фазы)

- Папки и дерево папок (Фаза 3)
- Drag-and-drop (Фаза 3)
- Теги (Фаза 3)
- Поиск (Фаза 3)
- Wiki-ссылки (Фаза 4)
- История версий (Фаза 4)
- Корзина (Фаза 4)
- Admin панель (Фаза 4)
