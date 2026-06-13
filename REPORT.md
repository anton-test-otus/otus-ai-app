# Отчёт о рефакторинге

Документ фиксирует **проблемы** и **способы их решения**, возникшие при рефакторинге. Спецификации фич и планы реализации — в `PHASES.md`, `ARCHITECTURE.md` и тематических файлах (например [`demoseed.md`](./demoseed.md)).

---

## Архитектура

Данные по архитектуре и фазам собирались на модели Opus 4.5 в режиме плана (см. промпты 1-5).

Для генерации кода переключился на Sonnet 4.5

## Фаза 1: Основа

---

## Проблема 1: JWT ключи генерировались вручную после запуска контейнеров

**Описание проблемы:**
JWT ключи (private.pem, public.pem) генерировались вручную через команду `docker exec` после запуска контейнеров. Это создавало следующие проблемы:
- Ключи могли попасть в систему контроля версий
- Требовались ручные действия при развёртывании
- Отсутствовала автоматизация процесса

**Решение:**
Перенесена генерация ключей в Dockerfile на этап сборки образа:
- Добавлена команда генерации в `docker/php/Dockerfile`
- Ключи генерируются автоматически при `docker compose build`
- Директория `config/jwt/` добавлена в `.gitignore`

---

## Проблема 2: Конфиденциальные данные в репозитории

**Описание проблемы:**
Файл `.env` с реальными паролями и секретами находился под контролем версий Git:
- APP_SECRET
- JWT_PASSPHRASE
- Пароли базы данных
- Другие конфиденциальные данные

**Решение:**
- Создан `.env.example` как шаблон с примерами значений
- Реальный `.env` добавлен в `.gitignore`
- Создан Makefile с командой для копирования `.env.example` в `.env`
- Все конфиденциальные значения заменены на плейсхолдеры в `.env.example`

---

## Проблема 3: Неудобная структура переменных окружения

**Описание проблемы:**
Использовалась переменная `DATABASE_URL` в формате DSN, что затрудняло:
- Переопределение отдельных параметров БД
- Использование в docker-compose.yml
- Чтение конфигурации

**Решение:**
Разделены переменные базы данных на отдельные компоненты:
- `DB_HOST` - хост базы данных
- `DB_PORT` - порт базы данных
- `DB_NAME` - имя базы данных
- `DB_USER` - пользователь базы данных
- `DB_PASSWORD` - пароль базы данных
- `DATABASE_URL` строится из этих переменных в .env

---

## Проблема 4: Отсутствие автоматизации развёртывания

**Описание проблемы:**
Для запуска проекта требовалось выполнять множество команд вручную:
- Копирование конфигурационных файлов
- Запуск контейнеров
- Установка зависимостей
- Применение миграций
- Создание администратора

**Решение:**
Создан Makefile с командами:
- `make init` - первоначальная настройка проекта
- `make build` - сборка Docker образов
- `make up` - запуск контейнеров
- `make install` - установка зависимостей
- `make migrate` - применение миграций
- `make admin` - создание администратора из .env
- `make down` - остановка контейнеров
- `make logs` - просмотр логов

---

## Проблема 5: Невозможность управления ролями пользователей

**Описание проблемы:**
Отсутствовал endpoint для назначения и снятия роли ROLE_ADMIN у существующих пользователей. Только первый зарегистрированный пользователь получал роль администратора автоматически.

**Решение:**
Добавлены endpoints в AdminController:
- `PATCH /api/admin/users/{id}/promote` - назначить роль ROLE_ADMIN
- `PATCH /api/admin/users/{id}/demote` - снять роль ROLE_ADMIN
- Оба endpoint доступны только для пользователей с ROLE_ADMIN

---

## Проблема 6: Небезопасное открытие порта PostgreSQL

**Описание проблемы:**
В `docker-compose.yml` порт PostgreSQL (5432) был открыт наружу через маппинг `"5432:5432"`. Это создавало проблемы:
- **Безопасность**: база данных доступна извне контейнера
- **Конфликты портов**: невозможно запустить, если локально установлен PostgreSQL
- **Избыточность**: к PostgreSQL обращаются только сервисы внутри Docker сети

**Решение:**
Удалён маппинг портов для PostgreSQL:
- Сервис `postgres` больше не публикует порт 5432
- База данных доступна только внутри Docker сети по имени хоста `postgres`
- PHP контейнер подключается к БД через внутреннюю сеть
- Улучшена безопасность: БД не доступна с хост-машины

Для прямого доступа к БД в целях отладки можно использовать:
```bash
docker exec -it otus_postgres psql -U otus_user -d otus_ai_db
```

---

## Проблема 7: Хардкод порта приложения в docker-compose.yml

**Описание проблемы:**
Порт приложения (8080) был захардкожен в `docker-compose.yml`:
- Невозможно было легко изменить порт без редактирования docker-compose.yml
- Для продакшена обычно используется порт 80 или 443
- Nginx внутри контейнера правильно слушает порт 80, но маппинг на хост был статичным

**Решение:**
Вынесена переменная `APP_PORT` в `.env.example`:
- По умолчанию: `APP_PORT=8080` (удобно для разработки, не требует sudo)
- Для продакшена можно установить `APP_PORT=80`
- Nginx внутри контейнера продолжает слушать стандартный порт 80
- Маппинг настраивается через переменную: `${APP_PORT:-8080}:80`

---

## Фаза 2: Основные функции заметок

---

## Проблема 8: Критические уязвимости в axios

**Описание проблемы:**
При проверке безопасности зависимостей фронтенда обнаружены множественные критические CVE в axios 1.7.9:
- **CVE-2026-42033** - Prototype Pollution (перехват/модификация JSON ответов)
- **CVE-2026-42035** - Header Injection via Prototype Pollution
- **CVE-2026-42042** - XSRF Token Exposure (утечка токенов на cross-origin серверы)
- **CVE-2026-40175** - Cloud Metadata Exfiltration/SSRF (RCE в облачных средах)
- Дополнительно: axios был скомпрометирован в supply chain атаке в марте 2026

Минимальная безопасная версия: axios@1.15.1 (апрель 2026)

**Решение:**
Вместо обновления axios принято решение полностью отказаться от него в пользу native fetch API:
- Создан собственный API клиент на базе fetch (~120 строк кода)
- Реализованы request/response interceptors через hooks
- Добавлена обработка JWT токенов
- Автоматический редирект на login при 401
- Query parameters поддержка
- Типизированные ошибки (HttpError класс)

**Преимущества решения:**
- 0 известных CVE
- Удалено 22 пакета (axios + транзитивные зависимости)
- Полный контроль над HTTP логикой
- Меньший bundle size
- Образовательная ценность для учебного проекта

---

## Проблема 9: XSS уязвимость в Milkdown

**Описание проблемы:**
Обнаружена уязвимость в Milkdown 7.5.3:
- **AIKIDO-2025-10253** - Cross-Site Scripting (XSS)
- Уязвимые версии: 7.3.0 - 7.8.0
- Исправлено в версии 7.9.0

**Решение:**
Обновлены все пакеты @milkdown/* с версии 7.5.3 до 7.9.0:
- @milkdown/core
- @milkdown/ctx
- @milkdown/vue
- @milkdown/prose
- @milkdown/preset-commonmark
- @milkdown/preset-gfm
- @milkdown/plugin-history
- @milkdown/plugin-listener
- @milkdown/theme-nord

---

## Проблема 10: Несуществующие версии зависимостей

**Описание проблемы:**
При первом запуске node контейнера обнаружены ошибки установки:
- `@vee-validate/zod@^4.15.2` - версия не существует
- `@vueuse/core@^11.4.0` - версия не существует

**Решение:**
Исправлены версии на актуальные и стабильные:
- `@vee-validate/zod`: 4.15.2 → 4.15.1 (latest stable, июнь 2025)
- `vee-validate`: 4.15.2 → 4.15.1
- `@vueuse/core`: 11.4.0 → 14.3.0 (latest stable, май 2026)

**Проверка безопасности:**
- vee-validate 4.15.1: 0 уязвимостей, Health Score 86/100, почти год в production
- @vueuse/core 14.3.0: 0 уязвимостей, 7.7M weekly downloads

---

## Фаза 3: Организация

---

## Проблема 11: Недостаток архитектуры - избыточное поле position

**Описание проблемы:**
При проектировании архитектуры (фаза планирования) в сущности `Folder` и `Note` было добавлено поле `position` для управления порядком отображения элементов. Однако при реализации фазы 3 выяснилось:

**Для папок:**
- Папки должны сортироваться по имени (алфавитный порядок)
- Поле `position` не имеет смысла, так как пользователь не может контролировать порядок папок
- Добавляет сложность в логику (нужно управлять значениями position при создании/перемещении)

**Для заметок:**
- Заметки сортируются по дате обновления (updated_at) для показа последних изменений
- Пользователь не перетаскивает заметки для изменения порядка внутри папки
- Поле `position` добавляет ненужную сложность без реальной пользы

**Причина ошибки в архитектуре:**
- Чрезмерное использование паттерна "position для сортировки" без анализа реальных требований
- Недостаточное обсуждение UX: как пользователь будет организовывать заметки?
- Преждевременная оптимизация: добавлено поле "на будущее"

**Решение (рефакторинг):**
1. Удалено поле `position` из сущностей `Folder` и `Note`
2. Удалены методы `getPosition()` и `setPosition()` из обеих сущностей
3. Исправлена миграция `Version20260609085911` (приложение не в продакшене)
4. Обновлены TypeScript типы на фронтенде
5. Папки сортируются по `name ASC` (алфавитный порядок)
6. Заметки сортируются по `updated_at DESC` (последние изменения сверху)

**Пересоздание БД:**
```bash
docker compose down -v
docker compose up -d
docker exec otus_php bin/console doctrine:migrations:migrate --no-interaction
```

**Результат:**
- ✅ Упрощенная модель данных без избыточных полей
- ✅ Понятная и предсказуемая сортировка для пользователя
- ✅ Меньше кода для поддержки (нет логики управления position)
- ✅ Чистая история миграций

**Урок:**
Каждое поле в БД должно иметь четкую цель и соответствовать реальным требованиям. Добавление полей "на всякий случай" усложняет систему без пользы.

---

## Проблема 12: Избыточные режимы отображения редактора

**Описание проблемы:**
Редактор заметок (NoteView.vue) имел три режима отображения:
- `edit` - только редактор
- `split` - редактор + превью одновременно (по умолчанию)
- `preview` - только превью

Это создавало следующие проблемы:
- **UX неоднозначность**: непонятно, как должна открываться заметка по умолчанию
- **Избыточный UI**: SelectButton с тремя вариантами занимал место и усложнял интерфейс
- **Случайные изменения**: открытие заметки сразу в режиме редактирования (или split) опасно
- **Мобильная версия**: split режим бесполезен на маленьких экранах, но занимал место в UI
- **Неявное поведение**: клик по заметке в Dashboard открывал её в split режиме

**Причина проблемы:**
- Чрезмерное копирование паттернов из других приложений (Notion, Obsidian)
- Попытка угодить всем сценариям использования сразу
- Недостаточный анализ реальных потребностей пользователей

**Решение (рефакторинг):**

1. **Упрощение типа ViewMode:**
   ```typescript
   // До: export type ViewMode = 'edit' | 'preview' | 'split'
   // После: export type ViewMode = 'edit' | 'preview'
   ```

2. **Изменение UI в NoteView.vue:**
   - Удален SelectButton с тремя вариантами (desktop и mobile)
   - Добавлена одна кнопка переключения в тулбаре:
     - В режиме preview: кнопка "Редактировать" (карандаш)
     - В режиме edit: кнопка "Просмотр" (глаз)
   - Отображается либо редактор, либо превью (без одновременного показа)

3. **Четкие правила открытия заметок:**
   - **Новые заметки** → режим `edit` (создаются для редактирования)
   - **Клик по заметке в списке** → режим `preview` (безопасный просмотр)
   - **Клик по кнопке карандаша** → режим `edit` (явное намерение редактировать)
   - **Wiki-ссылки** → режим `preview` (переход для чтения)
   - **Результаты поиска** → режим `preview` (просмотр найденного)
   - **Обратные ссылки** → режим `preview` (навигация по связям)

4. **Режим в URL query параметрах:**
   ```typescript
   // Пример: /notes/uuid?mode=preview
   router.push({ name: 'note', params: { id }, query: { mode: 'preview' } })
   ```
   - Позволяет шарить ссылки с нужным режимом
   - `router.replace()` для переключения без записи в историю

5. **Обновленные компоненты:**
   - DashboardView.vue - разделены `openNote()` и `openNoteInEditMode()`
   - MarkdownPreview.vue - wiki-ссылки открывают в preview
   - SearchBar.vue - результаты открывают в preview
   - AppNavbar.vue - новая заметка открывает в edit
   - BacklinksPanel.vue - ссылки открывают в preview

**Результат:**
- ✅ Простой и понятный интерфейс с одной кнопкой переключения
- ✅ Предсказуемое поведение: просмотр по умолчанию, редактирование по запросу
- ✅ Безопасность: нет случайных изменений при открытии заметки
- ✅ Меньше кода: удалено ~50 строк UI логики
- ✅ Лучший mobile UX: нет бесполезного split режима
- ✅ Явное намерение: кнопка карандаша = редактирование

**Урок:**
Не нужно копировать все фичи из других приложений. Простой и предсказуемый UX часто лучше, чем множество опций. "Режим просмотра по умолчанию" - это безопасная практика для любого приложения с редактированием контента.

---

## Проблема 12: PUT создавал дубликаты заметок вместо обновления

**Дата:** 2026-06-10

**Симптомы:**
- Переименование заметки (автосохранение title через PUT) создавало новую запись с другим UUID
- Старый заголовок оставался в списке, появлялся дубликат с новым именем
- Та же проблема затрагивала PUT для папок и тегов

**Причина:**
Кастомные `NoteProcessor`, `FolderProcessor` и `TagProcessor` вызывали `$em->persist()` напрямую, полностью обходя `api_platform.doctrine.orm.state.persist_processor`. В API Platform 4 стандартный `PersistProcessor` для PUT мержит входные данные в существующую managed-сущность через `$context['previous_data']`. Без этого Doctrine создавал новую entity с новым UUID.

**Решение:**
1. В процессоры добавлена делегация в `PersistProcessor` через DI (`#[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]`)
2. DELETE и soft delete оставлены в кастомной логике (до делегации)
3. Для истории версий: снимок `title/content` берётся из `previous_data` до persist, версия создаётся после persist через новый метод `NoteVersionService::createVersionSnapshot()` — чтобы связь `NoteVersion#note` указывала на managed-сущность

**Затронутые файлы:**
- `backend/src/State/NoteProcessor.php`
- `backend/src/State/FolderProcessor.php`
- `backend/src/State/TagProcessor.php`
- `backend/src/Service/NoteVersionService.php`

---

## Проблема 13: Рефакторинг логики версий заметок

**Дата:** 2026-06-10

**Контекст:**
Временный метод `createVersionSnapshot()` решал проблему detached-сущности при PUT, но не давал сравнивать старое и новое состояние. По плану нужна полноценная логика консолидации версий при автосохранении.

**Решение:**
1. Добавлен `NoteSnapshot` (`title` + `content`) с фабриками `fromNote()` / `fromVersion()` и `equals()`
2. `NoteVersionService::recordVersionOnUpdate()` — единая точка входа при PUT:
   - пропуск, если `previousState === newState` (нет изменений в запросе);
   - пропуск, если `newState` совпадает с последней версией (пробел + backspace);
   - в окне 5 минут — обновление последней версии снимком `previousState`;
   - иначе — создание новой версии
3. `backupCurrentState()` — для восстановления из истории (замена старого `createVersion()`)
4. `NoteProcessor` передаёт снимки из `previous_data` и входящих данных, версия создаётся после `PersistProcessor`

**Затронутые файлы:**
- `backend/src/Dto/NoteSnapshot.php`
- `backend/src/Service/NoteVersionService.php`
- `backend/src/Repository/NoteVersionRepository.php`
- `backend/src/State/NoteProcessor.php`

---

## Проблема 14: Не отображались версии заметки в UI

**Дата:** 2026-06-10

**Симптомы:**
- Панель «Version History» не показывала версии (пустой список или 404)
- В БД версии создавались при автосохранении
- `GET /api/note_versions` работал, `GET /api/notes/{noteId}/versions` — нет

**Причина:**
В `NoteVersion.php` для кастомных операций с `{noteId}` в URI не были настроены `uriVariables` (Link). API Platform 4 возвращал 404 `Invalid uri variables` до вызова провайдера.

**Решение:**
Добавлены `uriVariables` с `Link(fromClass: Note::class, toProperty: 'note')` для списка версий и restore.

**Затронутые файлы:**
- `backend/src/Entity/NoteVersion.php`

---

## Рефакторинг: адаптивный layout боковых панелей

**Проблема:**
- Floating-кнопки сайдбаров (`fixed top-[4.5rem]`) визуально оторваны от navbar
- Разные breakpoints у левого (`lg`) и правого (`md`) сайдбаров сжимали редактор на планшете
- Дублирование логики fixed/drawer в `AppSidebar` и `NoteMetadata`

**Решение:**
- Общий компонент `AppSidePanel` (fixed + spacer + drawer)
- Левый сайдбар: fixed `≥ 1024px`, drawer `< 1024px`, toggle в navbar
- Правый сайдбар метаданных (только `NoteView`): fixed `≥ 1400px`, drawer `< 1400px`, toggle в toolbar заметки
- `useLayoutPanels` (provide/inject) для связи navbar ↔ sidebar

**Затронутые файлы:**
- `frontend/src/components/layout/AppSidePanel.vue` (новый)
- `frontend/src/composables/useLayoutPanels.ts` (новый)
- `frontend/src/components/layout/AppSidebar.vue`
- `frontend/src/components/layout/NoteMetadata.vue`
- `frontend/src/components/layout/AppLayout.vue`
- `frontend/src/components/layout/AppNavbar.vue`
- `frontend/src/views/NoteView.vue`
- `frontend/src/composables/useBreakpoints.ts`

---

## Консолидация версий: окно по updatedAt заметки

**Проблема:** Окно консолидации ошибочно считалось от `createdAt` версии или обновляло существующую версию in-place.

**Решение:**
- `NoteVersion` хранит только `createdAt`; версии **не изменяются** после создания
- Новая версия создаётся только если с момента **предыдущего** `updatedAt` заметки (до текущего PUT) прошло ≥ N минут
- Внутри окна N минут правки попадают только в заметку, без новых записей в истории
- `backupCurrentState` при восстановлении версии по-прежнему всегда создаёт снимок (если содержимое отличается)

**Затронутые файлы:**
- `backend/src/Service/NoteVersionService.php`
- `backend/src/State/NoteProcessor.php`

---

## Пользовательские настройки автосохранения и версионирования

**Задача:** Вынести интервалы автосохранения и версионирования из захардкоженных/env-only значений в настраиваемые параметры пользователя.

**Решение:**
- Nullable-поля в `users`: `autosave_delay_seconds` (5/10/15/30/60), `version_consolidation_window_minutes` (1–60)
- `NULL` в БД = использовать системный default из env (не записывать число при «очистке» селекта — при смене env default подхватится автоматически)
- `UserSettingsResolver` — единая точка разрешения effective-значений
- `NoteVersionService` принимает окно консолидации параметром (per-user через `NoteProcessor`)
- API: `GET /api/auth/me` и `PATCH /api/auth/settings` возвращают `settings` + `defaults`
- Frontend: `SettingsView`, `useUserSettings`, клик по email в navbar → `/settings`

**Затронутые файлы:**
- `backend/src/Entity/User.php`, `backend/migrations/Version20260609085911.php`
- `backend/src/Service/UserSettingsResolver.php`, `backend/src/Dto/UpdateUserSettingsDto.php`
- `backend/src/Controller/AuthController.php`, `backend/src/EventListener/JWTCreatedListener.php`
- `frontend/src/views/SettingsView.vue`, `frontend/src/composables/useUserSettings.ts`

---

## Переработка футера левого сайдбара

**Задача:** Перенести системную навигацию (корзина, админка, аккаунт) из navbar в footer левого сайдбара.

**Решение:**
- Компонент `SidebarFooter.vue` в секции footer `AppSidePanel` (слот `#footer`)
- Layout sidebar: flex-колонка — скроллируемые папки/теги сверху, footer закреплён внизу (fixed panel и mobile drawer)
- `stores/trash.ts` — счётчик удалённых заметок для badge; обновление при удалении заметки и на странице корзины
- Navbar упрощён до: лого, toggle навигации, поиск, «Новая заметка»
- `useLayoutPanels.closeNavigation()` — закрытие drawer при клике по пунктам footer на мобильных

**Затронутые файлы:**
- `frontend/src/components/sidebar/SidebarFooter.vue`, `frontend/src/stores/trash.ts`
- `frontend/src/components/layout/AppSidePanel.vue`, `AppSidebar.vue`, `AppNavbar.vue`, `AppLayout.vue`
- `frontend/src/composables/useLayoutPanels.ts`, `frontend/src/styles/main.css`

---

## Кнопка «Новая заметка» (фаза 9)

**Задача:** Зафиксировать видимость кнопки в navbar и контекст при создании заметки (папка/теги).

**Решение:**
- `AppNavbar` — кнопка скрыта на странице корзины (`route.name === 'trash'`); guest-страницы не используют navbar
- `useCreateNote.resolveNewNoteContext` — на странице открытой заметки (`route.name === 'note'`) наследуются папка и теги `currentNote`; на остальных страницах — `selectedFolderId`, теги не копируются
- Контекст передаётся в query (`folderId`, `tags`); `NoteView.initDraft` читает теги из query
- `CreateNoteRequest` и `notesApi.create` — поддержка `tags` при POST (IRI через `resolveTagNamesToIris`); убран отдельный `updateNote` для тегов после создания черновика

**Затронутые файлы:**
- `frontend/src/components/layout/AppNavbar.vue`
- `frontend/src/composables/useCreateNote.ts`
- `frontend/src/views/NoteView.vue`
- `frontend/src/types/index.ts`, `frontend/src/api/notes.ts`

---

## Теги в редакторе заметки

**Проблема:** `NoteTagsEditor` использовал `tagsStore.tags`, который в сайдбаре фильтруется по выбранной папке — при редактировании заметки были доступны не все теги пользователя.

**Решение:** редактор загружает полный список тегов через `tagsApi.getAll()` без `folderId` в локальное состояние; создание новых тегов идёт через API напрямую, без перезаписи отфильтрованного списка сайдбара.

**Затронутые файлы:** `frontend/src/components/common/NoteTagsEditor.vue`

---

## Тулбар NoteView на широких экранах (≥ 3xl)

**Проблема:** на экранах ≥ 1400px фиксированная панель метаданных справа перекрывала кнопки тулбара (редактирование, просмотр, удаление) — тулбар занимал всю ширину main, а spacer метаданных был только у области редактора.

**Решение:** layout `NoteView` перестроен: тулбар и редактор в одной колонке слева, `NoteMetadata` — соседний flex-элемент справа (как у левого сайдбара). Spacer панели метаданных резервирует место и для тулбара.

**Затронутые файлы:** `frontend/src/views/NoteView.vue`

---

## Контекст тегов при создании заметки

**Проблема:** новая заметка не получала теги из фильтра dashboard и актуальные теги открытой заметки (брались устаревшие данные из `currentNote`).

**Решение:**
- `useCreateNote` — на страницах заметки контекст из `syncActiveNoteContext` (живые `noteTags` / `noteFolderId`); на остальных страницах — теги из `tagsStore.selectedTags` (имена по id)
- `NoteView` синхронизирует контекст при загрузке, инициализации черновика и изменении папки/тегов

**Затронутые файлы:** `frontend/src/composables/useCreateNote.ts`, `frontend/src/views/NoteView.vue`

---

## Массовое создание черновиков (POST /notes)

**Проблема:** при автосохранении и уходе со страницы `/note-new` параллельно вызывался `persistDraftNote` из нескольких путей (`useAutosave`, `leaveNote`, `onBeforeRouteLeave`, `goBack`) без общего mutex и без проверки «уже сохранено». У пользователя `test@test.local` за одну секунду создалось 1132 дубликата «Внутренняя заметка» (версии заметок тут не при чём — в `note_versions` одна запись).

**Решение:**
- общий `persistDraftPromise` — все параллельные вызовы ждут один `POST /notes`;
- проверка `hasUnsavedChanges()` перед созданием черновика;
- `leaveNote` унифицирован через `flushSave()` (тот же mutex `activeSave` в `useAutosave`).

**Затронутые файлы:** `frontend/src/views/NoteView.vue`

---

## Состояния загрузки и обработка ошибок (фаза 12)

**Задача:** Единые паттерны loading / empty / error; toast или inline для сбоев API; убрать ad-hoc `isLoading` и дублирование Toast.

**Решение:**
- `LoadingState.vue` — центрированный спиннер (`compact` для sidebar / панелей)
- `ErrorState.vue` — inline-ошибка с кнопкой «Повторить» (`compact` для узких панелей)
- `useAppToast` — обёртка над PrimeVue Toast: `showSuccess` / `showError` / `showInfo` через `getApiErrorMessage`
- `Toast` и `ConfirmDialog` перенесены в `AppLayout` (один глобальный экземпляр)
- Stores (`notes`, `folders`, `tags`) и `useNoteVersions` — ошибки через `getApiErrorMessage`
- Views и sidebar: порядок `loading` → `error` → `empty` → контент; ошибки загрузки — `ErrorState`, мутации — toast
- `NoteView`: при сбое загрузки заметки — `ErrorState` с повтором вместо редиректа на dashboard

**Затронутые файлы:**
- `frontend/src/components/common/LoadingState.vue`, `ErrorState.vue`
- `frontend/src/composables/useAppToast.ts`
- `frontend/src/components/layout/AppLayout.vue`
- `frontend/src/stores/notes.ts`, `folders.ts`, `tags.ts`
- `frontend/src/views/DashboardView.vue`, `NoteView.vue`, `TrashView.vue`, `TagsView.vue`, `SettingsView.vue`, `admin/AdminUsersView.vue`
- `frontend/src/components/sidebar/TagsPanel.vue`, `FolderTree.vue`, `FolderTreeItem.vue`
- `frontend/src/components/editor/VersionHistoryPanel.vue`
- `frontend/src/composables/useFavoriteToggle.ts`, `useNoteVersions.ts`

---

## Подгрузка заметок вместо пагинации (фаза 12)

**Задача:** Infinite scroll на dashboard (и поиск) вместо кнопок пагинации.

**Решение:**
- `notesStore.fetchNotes` — режим `append` для следующих страниц; `loadMoreNotes`, `isLoadingMore`, `hasMore`; дедупликация in-flight запросов; сброс списка при смене фильтров
- Composable `useInfiniteList` — sentinel + `IntersectionObserver` с автоопределением scroll-контейнера (`main` в `AppLayout`)
- `DashboardView` — убран `Paginator`, индикатор `LoadingState compact` внизу при подгрузке
- `SearchBar` — полный поиск в модалке по тому же паттерну (append + sentinel)

**Затронутые файлы:**
- `frontend/src/composables/useInfiniteList.ts`
- `frontend/src/stores/notes.ts`
- `frontend/src/views/DashboardView.vue`
- `frontend/src/components/common/SearchBar.vue`

---

## Избранное — отдельная вкладка (фаза 12)

**Задача:** Вынести избранные заметки из dashboard в отдельный вид; пункт «Избранное» в sidebar перед папками.

**Решение:**
- Убран блок «Избранные» с `DashboardView`; избранные доступны на вкладке `/favorites` и в списках dashboard/папок
- Маршрут `/favorites`, `FavoritesView` с `NoteCard`, infinite scroll через `fetchFavorites` / `loadMoreFavorites`
- В store — отдельные `favoritesPagination`, loading/error-флаги; `fetchFavorites` больше не вызывается из `fetchNotes`
- Компонент `FavoritesNavLink` в sidebar над `FolderTree`; «Все заметки» подсвечивается только на dashboard без выбранной папки
- Уточнение: избранные включены в списки dashboard и папок (со звёздочкой); вкладка «Избранное» — отдельная выборка только избранных

**Затронутые файлы:**
- `frontend/src/views/FavoritesView.vue`, `DashboardView.vue`
- `frontend/src/components/sidebar/FavoritesNavLink.vue`, `FolderTree.vue`
- `frontend/src/components/layout/AppLayout.vue`
- `frontend/src/stores/notes.ts`, `frontend/src/api/notes.ts`
- `frontend/src/router/index.ts`

---

## Alias wiki-ссылок в note_links (фаза 14.1)

**Задача:** Хранить alias (и порядок вхождений) wiki-ссылок в `note_links` для подписей рёбер графа (фаза 14.2).

**Решение:**
- Миграция `Version20260613120000`: колонка `aliases JSONB NOT NULL DEFAULT '[]'`
- `NoteLink.aliases` — массив `(string|null)[]`; пустой alias после trim → `null`
- `NoteLinkSyncService::syncFromContent()` — `parseLinksWithAliases()` → группировка по target → upsert существующих строк / удаление устаревших (вместо delete-all + recreate)
- `NoteProcessor` делегирует синхронизацию сервису при POST/PUT/PATCH
- Отдельная backfill-команда не нужна: dev-БД можно сбросить; в prod alias появятся при пересохранении заметок

**Затронутые файлы:**
- `backend/migrations/Version20260613120000.php`
- `backend/src/Entity/NoteLink.php`
- `backend/src/Service/NoteLinkSyncService.php`
- `backend/src/State/NoteProcessor.php`

---

## API графа и linkStats (фаза 14.2)

**Задача:** Endpoint локального subgraph wiki-связей и счётчики `linkStats` в `note:read` для UI кнопки «Граф связей» (фаза 14.3).

**Решение:**
- `GET /api/notes/{id}/graph` в `WikiLinkController`: query `depth` (1–3, default 2), `direction` (`both` \| `outgoing` \| `incoming`)
- `NoteGraphService::buildSubgraph()` — BFS от корневой заметки, max 120 узлов; `truncated` + `frontierNodeIds` если есть не включённые соседи (лимит depth или maxNodes)
- `NoteLinkRepository::findLinksForNode()` — исходящие/входящие связи с фильтром удалённых заметок; `countLinkStats()` — incoming/outgoing для `NoteReadNormalizer`
- Ответ графа: `nodes[]` (id, title, folderId, isFavorite), `edges[]` (id, source, target, aliases); подписи рёбер — на клиенте (14.3)

**Затронутые файлы:**
- `backend/src/Service/NoteGraphService.php`
- `backend/src/Repository/NoteLinkRepository.php`
- `backend/src/Serializer/NoteReadNormalizer.php`
- `backend/src/Controller/WikiLinkController.php`
- `backend/config/services.yaml`

---

## UI графа связей (фаза 14.3)

**Задача:** Визуализация локального subgraph wiki-связей вместо списка обратных ссылок в метаданных заметки.

**Решение:**
- Зависимость `vis-network@^10.1.0` (peer `vis-data` ≥ 8); установка только в Docker-контейнере `node`
- `NoteLinksGraphPanel` в `NoteView` / `NoteMetadata`: кнопка с badge `incoming↔outgoing` по `linkStats` из `note:read`
- `NoteLinksGraphDialog` — PrimeVue `Dialog` (`MODAL_WIDTH.xl`), vis-network (forceAtlas2Based, directed edges, zoom/pan/drag)
- Подписи рёбер в `utils/noteGraph.ts`: первый alias или title target; при нескольких вхождениях — «alias ×N», полный список в tooltip
- Текущая заметка — группа `focus` (увеличенный узел); избранные — группа `favorite`; цвета адаптированы к light/dark через `useTheme`
- «+1 уровень» — повторный запрос `GET /graph?depth=N+1` с merge на клиенте (`mergeNoteGraphData`), до depth 3
- Клик по узлу → `/notes/:id?mode=preview`; loading / error / empty — `LoadingState`, `ErrorState`, `EmptyState`
- `BacklinksPanel.vue` удалён из UI; `GET /notes/{id}/backlinks` в API сохранён

**Затронутые файлы:**
- `frontend/package.json`, `frontend/package-lock.json`
- `frontend/src/api/wikilinks.ts`
- `frontend/src/types/index.ts` (`NoteLinkStats`)
- `frontend/src/utils/noteGraph.ts`
- `frontend/src/components/notes/NoteLinksGraphPanel.vue`
- `frontend/src/components/notes/NoteLinksGraphDialog.vue`
- `frontend/src/views/NoteView.vue`
- удалён `frontend/src/components/BacklinksPanel.vue`

---

## Рефакторинг UI графа связей

**Задача:** Упростить читаемость графа: название заметки на узле, alias — только при hover на ребро.

**Решение:**
- Узлы: `shape: 'box'`, `shapeProperties.borderRadius: 8`, `label` с обрезанным title (max 28 символов), полное название — tooltip
- Рёбра: без видимых подписей; `title` с полным списком alias при наведении
- Убрана динамическая длина рёбер по длине подписи; фиксированный `springLength: 160`, усилено отталкивание узлов
- Взаимные ссылки — разведённые дуги (без `fontAlign`, который был нужен для подписей)
- Легенда и подсказки в `NoteLinksGraphDialog` обновлены под прямоугольные узлы

**Затронутые файлы:**
- `frontend/src/utils/noteGraph.ts`
- `frontend/src/components/notes/NoteLinksGraphDialog.vue`
- `ARCHITECTURE.md`

---

## Перенос графа и истории версий в тулбар

**Задача:** Устранить UX-проблему: кнопка истории в тулбаре открывала панель в закрытом сайдбаре метаданных; унифицировать точки входа.

**Решение:**
- Кнопки «Связанные заметки» и «История версий» — в тулбаре `NoteView` (после edit/preview), только иконки; **disabled**, если нет связей / версий (tooltip объясняет причину)
- `VersionHistoryDialog` — модалка со списком версий; `NoteLinksGraphPanel` удалён, граф открывается напрямую из тулбара
- Сайдбар метаданных: папка, теги, информация + `versionCount`
- `versionCount` в `note:read` через `NoteReadNormalizer` + `NoteVersionRepository::countByNote()`
- Паттерн fullscreen-модалок `< md`: `MODAL_FULLSCREEN_MOBILE_*` в `constants/modal.ts`, CSS в `main.css`

**Затронутые файлы:**
- `backend/src/Repository/NoteVersionRepository.php`, `backend/src/Serializer/NoteReadNormalizer.php`
- `frontend/src/constants/modal.ts`, `frontend/src/styles/main.css`, `frontend/src/utils/version.ts`
- `frontend/src/types/index.ts`
- `frontend/src/views/NoteView.vue`
- `frontend/src/components/editor/VersionHistoryDialog.vue`, `VersionHistoryPanel.vue`, `RestoreVersionModal.vue`
- `frontend/src/components/notes/NoteLinksGraphDialog.vue`
- удалён `frontend/src/components/notes/NoteLinksGraphPanel.vue`
- `ARCHITECTURE.md`

---

## Фаза 18: Self-review — фронтенд

**Дата:** 2026-06-13

Систематический обход `frontend/src` по чеклисту фазы 18. Полный список замечаний, чеклисты и **14 шагов правок** — в [`frontend_selfreview.md`](./frontend_selfreview.md).

**Краткая сводка:** 1 critical (XSS в `SearchBar`), 23 medium, 13 low. Исправления — отдельными коммитами по шагам из `frontend_selfreview.md`; после каждого шага — отметка в чеклисте и запись в этом файле.

### Шаг 1: XSS в SearchBar (исправлено)

**Проблема:** `highlightMatch` оборачивал совпадения в `<mark>` без экранирования HTML в `note.title` / `contentPreview`; `v-html` выполнял произвольную разметку.

**Решение:**
- `utils/escapeHtml.ts` — экранирование `& < > " '`
- `utils/highlightMatch.ts` — сначала `escapeHtml(text)`, затем подсветка совпадений
- `SearchBar.vue` — импорт общей утилиты вместо локальной `highlightMatch`

**Затронутые файлы:**
- `frontend/src/utils/escapeHtml.ts` (новый)
- `frontend/src/utils/highlightMatch.ts` (новый)
- `frontend/src/components/common/SearchBar.vue`

### Шаг 2: raw HTML в markdown preview (исправлено)

**Проблема:** Milkdown (commonmark + gfm) пропускал raw HTML-блоки в DOM preview/editor без санитизации.

**Решение:**
- `remarkStripHtml.ts` — remark-плагин: mdast-узлы `html` → plain `text`
- Плагин подключён в `MarkdownPreview.vue` и `MarkdownEditor.vue` (печать через `NotePrintView` → `MarkdownPreview`)
- В комментарии к `sanitizeNoteText` зафиксировано: нормализация, не XSS defense

**Затронутые файлы:**
- `frontend/src/components/editor/remarkStripHtml.ts` (новый)
- `frontend/src/components/editor/MarkdownPreview.vue`
- `frontend/src/components/editor/MarkdownEditor.vue`
- `frontend/src/utils/sanitizeText.ts`

### Шаг 3: мёртвые npm-зависимости (исправлено)

**Проблема:** в `package.json` оставались неиспользуемые пакеты (`marked`, `vue-draggable-plus`, часть `@milkdown/*`); `unist-util-visit` и `@types/mdast` использовались в коде только как transitive.

**Решение:**
- Удалены: `marked`, `vue-draggable-plus`, `@milkdown/plugin-tooltip`, `@milkdown/theme-nord`, `@milkdown/vue` (−87 пакетов в lock)
- Добавлены явные зависимости: `unist-util-visit`, `@types/mdast` (dev)
- `vite build` проходит; `npm run build` — ok после правок типов (23 pre-existing `vue-tsc` ошибки в 9 файлах)

**Затронутые файлы:**
- `frontend/package.json`
- `frontend/package-lock.json`
- `frontend/src/components/common/SearchBar.vue`
- `frontend/src/components/editor/wikiLinkNode.ts`
- `frontend/src/components/layout/AppNavbar.vue`
- `frontend/src/components/layout/AppSidebar.vue`
- `frontend/src/components/layout/NoteMetadata.vue`
- `frontend/src/composables/useAppKeyboardShortcuts.ts`
- `frontend/src/directives/tooltip.ts`
- `frontend/src/utils/noteGraph.ts`
- `frontend/src/views/DashboardView.vue`

### Шаг 4: мёртвый код store/API (исправлено)

**Проблема:** неиспользуемые методы и exports засоряли публичный API stores и `wikilinks` API; `folderTree` — лишний alias над `folders`.

**Решение:**
- Удалены `searchNotes` (`notes` store), `getTagNotes` (`tags` store), `getBacklinks` + `BacklinkNote` (`api/wikilinks.ts`)
- Удалены неиспользуемые `wikiLinkPreviewPlugins` / `wikiLinkPlugins` из `wikiLinkNode.ts`
- Удалены `folderTree` / `flatFolders` из `folders` store; в `AppLayout.vue` — `foldersStore.folders`

**Затронутые файлы:**
- `frontend/src/stores/notes.ts`
- `frontend/src/stores/tags.ts`
- `frontend/src/stores/folders.ts`
- `frontend/src/api/wikilinks.ts`
- `frontend/src/components/editor/wikiLinkNode.ts`
- `frontend/src/components/layout/AppLayout.vue`

### Шаг 5: JWT refresh (отложен)

Фронтенд шаг 5 пропущен до решения на бэкенде. Задача перенесена в [`backend_selfreview.md` — шаг 15](./backend_selfreview.md#шаг-15-jwt-refresh-блокирует-фронтенд-шаг-5). Следующий шаг фронта — **шаг 6** (Hydra parser).

### Шаг 6: единый парсер Hydra collection (исправлено)

**Проблема:** паттерн `(response['hydra:member'] || response['member'] || [])` и извлечение `totalItems` дублировались в 6 файлах тремя разными способами; в `folders.ts` — отдельная ветка для голого массива.

**Решение:**
- `utils/hydra.ts` — `parseHydraCollection<T>()` с поддержкой `hydra:member` / `member`, `hydra:totalItems` / `totalItems` и голого `T[]`; `??` вместо `||` для корректного `total = 0`
- Заменены все 8 вхождений в `api/notes.ts` (3), `api/trash.ts`, `api/tags.ts` (2), `api/folders.ts`, `composables/useNoteVersions.ts`
- `notesApi.filter` (`/notes/search`) не тронут — там кастомный `{ data, meta }`, не Hydra

**Затронутые файлы:**
- `frontend/src/utils/hydra.ts` (новый)
- `frontend/src/api/notes.ts`
- `frontend/src/api/trash.ts`
- `frontend/src/api/tags.ts`
- `frontend/src/api/folders.ts`
- `frontend/src/composables/useNoteVersions.ts`

### Шаг 7: единый API поиска заметок (исправлено)

**Проблема:** модалка wiki-ссылок после промежуточного рефакторинга использовала полнотекстовый `searchApi.search` (`title + content`); для выбора целевой заметки нужен поиск **только по title**.

**Решение:**
- `searchApi.searchByTitle()` → `GET /notes?title=...` (API Platform `SearchFilter partial`)
- `LinkNoteModal.vue` переведён на `searchByTitle`
- `SearchBar` — без изменений, полнотекст через `searchApi.search` → `/notes/search?q=...`
- `notesApi.filter` (dashboard) — без изменений

**Затронутые файлы:**
- `frontend/src/api/search.ts`
- `frontend/src/components/LinkNoteModal.vue`

**Smoke:** подтверждён пользователем (2026-06-13).

### Шаг 8: дедупликация paginated fetch в notes store (исправлено)

**Проблема:** `fetchNotes` и `fetchFavorites` (~90% идентичны): in-flight promise, append, pagination meta, error handling.

**Решение:**
- Вынесен общий `fetchPaginatedList()` в `stores/notes.ts` с параметрами: refs списка, in-flight state, `fetchFn`, `append`, `criteriaKey`, `errorMessage`
- `fetchNotes` / `fetchFavorites` — тонкие обёртки; `loadMoreNotes` / `loadMoreFavorites` без изменений сигнатур
- Отдельные `notesInFlight` / `favoritesInFlight` для dedup и criteriaKey (только notes)

**Затронутые файлы:**
- `frontend/src/stores/notes.ts`
- `frontend/src/composables/useInfiniteList.ts` — автоподгрузка без скролла (smoke)
- `frontend/src/components/layout/AppLayout.vue` — `min-h-0` / `100dvh` чтобы скролл был в `<main>`, не в `window`

**Smoke:** подтверждён пользователем (2026-06-13). Infinite scroll: viewport IO + scroll listeners; layout `min-h-0` в `AppLayout`.

### Backlog после ревью: регистронезависимый поиск

**Находка при smoke шага 6:** поиск по title регистрозависимый (`LinkNoteModal`, `SearchBar`). Задача вынесена в секцию «Доработки после ревью» в `frontend_selfreview.md` и `backend_selfreview.md`; основной фикс — на бэкенде (`NoteRepository::search`, `SearchFilter`).

### BE Шаг 2: валидация связей при записи (исправлено)

**Проблема:** при `POST`/`PUT`/`PATCH` заметки клиент мог указать IRI чужой папки или тегов; при смене `parent` у папки — IRI чужой или удалённой папки. `NoteProcessor` / `FolderProcessor` проверяли только владельца самой сущности.

**Решение:**
- `OwnedRelationAssert` — проверка `folder`, `tags`, `parent` на `user_id`; для parent — также `deletedAt IS NULL`
- Ответ **422** с русскоязычным сообщением (`UnprocessableEntityHttpException`)
- Вызов перед persist в `NoteProcessor` и `FolderProcessor`

**Затронутые файлы:**
- `backend/src/Security/OwnedRelationAssert.php` (новый)
- `backend/src/State/NoteProcessor.php`
- `backend/src/State/FolderProcessor.php`

**Проверка:** код-ревью подтверждено; ручной smoke не выполнялся — сценарии в [`future_autotests.md`](./future_autotests.md) («BE owned relations»).

### BE Шаг 3: sync wiki-ссылок после restore версии (исправлено)

**Проблема:** `RestoreVersionProcessor` восстанавливал `title`/`content` из версии, но не вызывал `NoteLinkSyncService::syncFromContent`. Таблица `note_links` оставалась от состояния до restore; для режима `copy` у новой заметки связи не создавались.

**Решение:** после всех режимов restore (`overwrite`, `create_version`, `copy`) — один вызов `syncFromContent($note)`; для `copy` в `$note` уже новая заметка.

**Затронутые файлы:**
- `backend/src/State/RestoreVersionProcessor.php`

**Проверка:** smoke подтверждён пользователем (2026-06-13).

---

