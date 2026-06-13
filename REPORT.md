# Отчёт о разработке

Этот документ отслеживает решения по рефакторингу, возникшие проблемы и их решения в процессе разработки.

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

## Demo seed (фаза 14.4) — предложение по реализации

**Задача:** Консольная команда для наполнения БД demo-данными (3 вселенные × ~40 заметок) — dev, скринкаст, ручная проверка графа связей.

**Ограничения:** новые Doctrine-сущности **не нужны**; не ходить через API Platform / HTTP — прямая работа с EntityManager и существующими сервисами.

### Команда

| Параметр | Значение |
|----------|----------|
| Имя | `app:seed-demo-data` |
| Флаг | `--force` — удалить demo-пользователей по email и пересоздать данные |
| Без `--force` | если любой из demo-email уже есть — предупреждение и exit 1 (не трогать чужие данные) |

**Demo-пользователи** (общий пароль `demo1234`):

| Email | Вселенная | Роль |
|-------|-----------|------|
| `hogwarts@demo.local` | Гарри Поттер | `ROLE_USER` |
| `westeros@demo.local` | Игра престолов | `ROLE_USER` |
| `witcher@demo.local` | Ведьмак | `ROLE_USER` (+ опционально `ROLE_ADMIN` для проверки админки) |

Удаление при `--force`: `UserRepository::findOneBy(['email' => …])` → `$em->remove($user)` → `flush()`; каскад через `orphanRemoval` на `notes`/`folders`/`tags`, FK на `note_links`/`note_versions` — CASCADE.

**Типичный dev-сценарий:**

```bash
docker compose exec php php bin/console app:reset-schema
docker compose exec php php bin/console app:seed-demo-data
# опционально: app:create-admin для отдельного админа из .env
```

### Структура кода (backend)

```
backend/src/
  Command/
    SeedDemoDataCommand.php          # CLI: --force, вывод статистики
  DemoSeed/
    DemoUniverseDefinition.php       # DTO: email, roles, folders[], notes[]
    DemoNoteDefinition.php           # key, title, folderPath?, content, isFavorite?
    DemoUniverseSeeder.php           # оркестратор одной вселенной
    Universe/
      PotterUniverse.php             # implements/returns DemoUniverseDefinition
      WesterosUniverse.php
      WitcherUniverse.php
```

- **Нет** отдельных Entity, **нет** Doctrine Fixtures bundle — только PHP-массивы/heredoc markdown в классах вселенных.
- Каждый `*Universe.php` — статический метод `definition(): DemoUniverseDefinition` (~800–1500 строк markdown суммарно на вселенную — нормально).

### Алгоритм `DemoUniverseSeeder`

1. Создать `User`, захешировать пароль (`UserPasswordHasherInterface`).
2. **Папки:** обойти дерево из definition (`['Хогвартс', 'Факультеты']` → path key `'Хогвартс/Факультеты'`); map `path → Folder`.
3. **Заметки (pass 1):** для каждой `DemoNoteDefinition`:
   - создать `Note` с `title`, `content` (пока с плейсхолдерами), `folder`, `isFavorite`;
   - map `noteKey → Note`.
4. **Wiki-ссылки (pass 2):** в content заменить плейсхолдеры на реальные UUID:
   - синтаксис в definition: `{{link:harry}}` → `[[uuid]]`, `{{link:harry|Мальчик-который-выжил}}` → `[[uuid|alias]]`;
   - regex replace по map `noteKey → (string) note->getId()`.
5. **Links:** для каждой заметки вызвать `NoteLinkSyncService::syncFromContent($note)` (не дублировать логику парсера).
6. Один `flush()` в конце вселенной (или batch по 20 заметок — по желанию при OOM).

Плейсхолдеры вместо UUID в definition — чтобы не генерировать контент вручную с заведомо несуществующими id и упростить читаемость фикстуры.

### Контент по вселенным (~40 заметок каждая)

**Potter (`hogwarts@demo.local`)**

| Папки (7) | Примеры заметок |
|-----------|-----------------|
| Хогвартс → Факультеты | Гриффиндор, Слизерин, … |
| Хогвартс → Предметы | Зельеварение, Защита от ДАР |
| Персонажи | Гарри, Гермиона, Дамблдор, Волан-де-Морт |
| Заклинания | Expelliarmus, Patronus, … |
| Артефакты | Мантия-невидимка, Философский камень |
| События | Турнир Трёх волшебников, Битва при Хогвартсе |
| *(корень)* | «Магическое сообщество», «Краткий путеводитель» |

**Westeros (`westeros@demo.local`)**

| Папки (8) | Примеры |
|-----------|---------|
| Дома → Север | Старки, Болтоны |
| Дома → Юг | Ланнистеры, Тиреллы |
| Персонажи | Нед, Арья, Тирион, Дейнерис |
| Локации | Винтерфелл, Королевская Гавань, Стена |
| Войны | Война Пяти королей, Long Night |
| Интриги | Красная свадьба, Purple Wedding |
| *(корень)* | «Печать: зима близка», «Совет мейстера» |

**Witcher (`witcher@demo.local`)**

| Папки (7) | Примеры |
|-----------|---------|
| Ведьмаки | Геральт, Лютик, Весемир |
| Чудовища | Грифон, Стрыга, Леший |
| Локации → Королевства | Новигруд, Velen, Skellige |
| Алхимия и знаки | Кошачий, Кваен, масла |
| Квесты | Семейное дело, Охота на грифона |
| *(корень)* | «Закон surprise», «Записи бестиария» |

У каждой вселенной **5–8 заметок `isFavorite: true`** (хабы и главные персонажи).

### Граф wiki-ссылок (идея связности)

Цель — локальный граф depth=2 выглядит насыщенно; не полный mesh.

- **1–2 hub-заметки** на вселенную — много исходящих ссылок (`[[uuid]]` без alias).
- **Персонажи** ссылаются друг на друга и на hub; часть ссылок с alias (`[[uuid|Профессор]]`).
- **События** — несколько ссылок на одних персонажей (проверка `aliases.length > 1` в 14.2).
- **3–5 заметок** только входящие ссылки (leaf) — для `incoming` badge.
- **Битых ссылок нет** — все `{{link:key}}` резолвятся в map.

Ориентир: ~60–90 исходящих `note_links` строк на пользователя (не 40, т.к. несколько ссылок на один target сливаются в одну строку с массивом aliases).

### Markdown в заметках

- H2/H3, списки, blockquote, **жирный**, `inline code`
- 1–2 заметки с таблицей (дома × девиз × регион; знаки × эффект)
- 1 заметка с fenced code block (например «рецепт зелья» / «клятва Night's Watch»)
- Длина тела: 200–800 символов в среднем, hub — до 1200

### Что сознательно не делаем

- Теги — в scope 14.4 не упоминались; можно добавить позже без смены архитектуры.
- `NoteVersion` — не сидить; версии появятся при редактировании в UI.
- Backfill / отдельная команда — только `app:seed-demo-data`.
- Фронтенд — без изменений.

### Документация после реализации

- `README.md` — секция «Demo-данные» с email/паролем и двумя командами reset + seed.
- `ARCHITECTURE.md` — строка в структуре `DemoSeed/` и список консольных команд.

### Порядок работ при реализации

1. DTO + `DemoUniverseSeeder` + smoke на 3 заметки без контента.
2. `SeedDemoDataCommand` с `--force`.
3. `PotterUniverse` полностью → проверка графа / backlinks в UI.
4. `WesterosUniverse`, `WitcherUniverse`.
5. README + отметить 14.4 ✅ в `PHASES.md`.

---

