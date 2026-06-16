# Self-review: бэкенд (фаза 18)

**Дата:** 2026-06-13  
**Область:** `backend/src`, `backend/config`, `backend/migrations`  
**Статус:** ревью выполнено; правки — отдельными коммитами по шагам ниже.

Исправления по каждому шагу можно коммитить отдельно. Внутри шага пункты с `- [ ]` — чеклист задач.

---

## Сводка

| Категория | Critical | Medium | Low |
|-----------|:--------:|:------:|:---:|
| Безопасность (IDOR / авторизация) | 1 | 4 | 1 |
| Запахи и структура | 0 | 3 | 2 |
| Дублирование | 0 | 3 | 2 |
| Мёртвый код / видимость API | 0 | 7 | 2 |
| N+1 / индексы / производительность | 0 | 5 | 2 |
| Согласованность паттернов | 0 | 4 | 2 |

---

## Шаг 1. Critical: изоляция данных по `user_id` (IDOR)

**Приоритет:** critical  
**Коммит:** `fix(backend): enforce resource ownership on item operations`

**Проблема:** `NoteUserExtension` фильтрует только **collection** `GET /notes`. Для **item**-операций (`GET`/`PUT`/`PATCH`/`DELETE` по UUID) API Platform загружает сущность по id **без проверки владельца**. Аналогично для `Folder`, `Tag`, `NoteVersion`, `NoteLink`.

Процессоры (`NoteProcessor`, `FolderProcessor`, `TagProcessor`) на update/delete **не проверяют**, что `$data->getUser() === $currentUser`.

**Сценарий:** пользователь A знает UUID заметки пользователя B → читает/изменяет/удаляет чужие данные.

**Файлы:** `Doctrine/Extension/NoteUserExtension.php`, `State/NoteProcessor.php`, `State/FolderProcessor.php`, `State/TagProcessor.php`, `Entity/NoteVersion.php`, `Entity/NoteLink.php`

- [ ] Добавить `QueryItemExtensionInterface` для `Note`, `Folder`, `Tag` (и при необходимости `NoteVersion`, `NoteLink`) — `andWhere entity.user = :currentUser`
- [ ] В процессорах на мутациях существующих сущностей — `assertSame($data->getUser(), $user)` или `AccessDeniedHttpException`
- [ ] Для `GET /notes/{id}` — отдавать 404 (не 403), если заметка чужая или в корзине (`deletedAt IS NOT NULL`), если так задумано для UX
- [ ] Smoke: один пользователь — свои CRUD и корзина без регрессии; IDOR A→B — см. [`future_autotests.md`](./future_autotests.md)

---

## Шаг 2. Валидация связей при записи (folder, tags, parent)

**Приоритет:** medium (часть attack surface шага 1)  
**Коммит:** `fix(backend): validate owned relations on note and folder write`

**Проблема:** при `POST`/`PUT`/`PATCH` заметки клиент может передать IRI папки или тегов **другого пользователя**. `NoteProcessor` не проверяет `folder.user` и `tag.user`. У `Folder` при смене `parent` — та же проблема.

**Файлы:** `State/NoteProcessor.php`, `State/FolderProcessor.php`

- [x] Перед persist: `folder === null || folder->getUser() === $user`
- [x] Для каждого тега в коллекции: `tag->getUser() === $user`
- [x] Для папки: `parent === null || parent->getUser() === $user`; parent не soft-deleted
- [x] Ошибка: `422` с понятным сообщением, не silent ignore
- [ ] Smoke: попытка привязать заметку к чужой папке — отказ *(отложено: [`future_autotests.md`](./future_autotests.md) — BE owned relations)*

---

## Шаг 3. Синхронизация wiki-ссылок после restore версии

**Приоритет:** medium  
**Коммит:** `fix(backend): sync note links after version restore`

**Проблема:** `RestoreVersionProcessor` вызывает `NoteVersionService::restoreFromVersion`, но **не** вызывает `NoteLinkSyncService::syncFromContent`. После восстановления старой версии `note_links` расходятся с `content`.

**Файлы:** `State/RestoreVersionProcessor.php`, `Service/NoteLinkSyncService.php`

- [x] После restore (все режимы `overwrite` / `create_version` / `copy`) — `syncFromContent` для затронутой заметки
- [x] Для режима `copy` — sync для **новой** заметки
- [x] Smoke: заметка с wiki-ссылками → restore версии без ссылок → `note_links` обновлены

---

## Шаг 4. Админка: защита от self-delete / self-demote

**Приоритет:** medium  
**Коммит:** `fix(backend): guard admin self-management edge cases`

**Проблема:** `AdminController` позволяет админу деактивировать/удалить **себя** и снять с себя `ROLE_ADMIN` без проверки «последний админ».

**Файл:** `Controller/AdminController.php`

- [x] `disable` / `delete`: запретить действие над `$user === $this->getUser()` (или требовать другого активного админа)
- [x] `demote`: не снимать `ROLE_ADMIN`, если это последний администратор в системе
- [x] Ответ: `400` / `409` с сообщением, не silent success
- [x] Smoke: единственный админ не может удалить себя

---

## Шаг 5. Регистрация: дубликат email

**Приоритет:** medium  
**Коммит:** `fix(backend): handle duplicate email on register`

**Проблема:** `AuthController::register` не проверяет уникальность email до `flush()`. При повторной регистрации — исключение Doctrine / `500`, а не `409`/`422`.

**Файл:** `Controller/AuthController.php`, `Entity/User.php`

- [x] Перед persist: `findOneBy(['email' => $email])` → `409` «Email уже занят»
- [x] Либо `#[UniqueEntity(fields: ['email'])]` на `User` + обработка в контроллере *(не делали: достаточно явной проверки в контроллере)*
- [x] Smoke: повторный POST `/api/auth/register` — предсказуемый HTTP-код

---

## Шаг 6. N+1: статистика пользователей в админке

**Приоритет:** medium  
**Коммит:** `perf(backend): batch admin user statistics`

**Проблема:** `AdminController::listUsers` вызывает `getUserStatistics($user)` **в цикле** — 5 SQL-запросов на каждого пользователя страницы.

**Файлы:** `Controller/AdminController.php`, `Repository/UserRepository.php`

- [x] `getUsersStatisticsBatch(array $userIds): array` — один-два агрегирующих запроса (notes/folders/tags/count/size/lastActivity)
- [x] Использовать в `listUsers`; `getUserStatistics` — обёртка над batch (в т.ч. для `getUserDetails`, 3 SQL вместо 5)
- [x] Smoke: список из 20 пользователей — заметно меньше запросов в profiler

---

## Шаг 7. N+1: `contentPreview` в списках заметок

**Приоритет:** medium  
**Коммит:** `perf(backend): batch wiki title resolution in list preview`

**Проблема:** `NoteListNormalizer` → `NotePreviewService::buildPreview` → `resolveWikiLinkTitles` → `findActiveByIdsForUser` **на каждую заметку** в collection. При 20 карточках — до 20 лишних запросов.

**Файлы:** `Serializer/NoteListNormalizer.php`, `Service/NotePreviewService.php`

- [ ] Собрать все UUID из wiki-ссылок без alias по всей странице → один batch `findActiveByIdsForUser`
- [ ] Передавать `titlesById` в `buildPreview` (опциональный аргумент)
- [ ] Альтернатива: context-aware normalizer с prefetch на уровне collection provider

---

## Шаг 8. N+1: `linkStats` и `versionCount` в `note:read`

**Приоритет:** medium  
**Коммит:** `perf(backend): combine note read metadata queries`

**Проблема:** `NoteReadNormalizer` на каждый `GET /notes/{id}` выполняет **3 запроса**: 2× `countLinkStats` + `countByNote`.

**Файлы:** `Serializer/NoteReadNormalizer.php`, `Repository/NoteLinkRepository.php`, `Repository/NoteVersionRepository.php`

- [ ] Один метод `getNoteReadMetadata(Note $note): { linkStats, versionCount }` с объединённым SQL или subselect
- [ ] Либо кэш в рамках одного request (менее предпочтительно)

---

## Шаг 9. Индексы и поиск по `LIKE`

**Приоритет:** medium / low  
**Коммит:** `perf(backend): add indexes for note list and search`

**Проблема:** частые фильтры `user_id + deleted_at IS NULL`, сортировка `updated_at DESC`, фильтр `is_favorite` — только индекс на `user_id`. Поиск `NoteRepository::search` и `SearchFilter` по `content` — `LIKE '%…%'` без full-text индекса.

**Файлы:** новая миграция, `Repository/NoteRepository.php`

- [ ] Составной индекс `(user_id, deleted_at)` или `(user_id, deleted_at, updated_at DESC)`
- [ ] Индекс `(user_id, is_favorite, updated_at)` — для избранных
- [ ] Документировать ограничение `LIKE` для MVP; опционально PostgreSQL `GIN` + `to_tsvector` для `/notes/search` (follow-up)
- [ ] Smoke: explain plan на типичных запросах dashboard/search

---

## Шаг 10. Мёртвый код: репозитории и сервисы

**Приоритет:** medium  
**Коммит:** `refactor(backend): remove dead repository and service methods`

| Символ | Файл | Статус |
|--------|------|--------|
| `findByUserWithPagination` | `NoteRepository` | нет вызовов |
| `countByUser` | `NoteRepository` | нет вызовов |
| `findByTitleCaseInsensitive` | `NoteRepository` | legacy title-ссылки удалены |
| `parseLinks` | `WikiLinkParser` | используется только `parseLinksWithAliases` |
| `getVersionsForNote` | `NoteVersionService` | репозиторий вызывается напрямую |
| `countVersionsForNote` | `NoteVersionService` | репозиторий вызывается напрямую |

- [x] Удалить неиспользуемые методы (или подключить, если нужны в фазе 20)
- [x] `replaceForPlainText` / `parseLinksWithAliases` — оставить (используются в preview)

---

## Шаг 11. Лишняя поверхность API: `NoteLink`, `NoteVersion` collection, backlinks

**Приоритет:** medium  
**Коммит:** `refactor(backend): narrow unused API resources`

**Проблема:**

| Endpoint | Статус |
|----------|--------|
| `GET/POST/DELETE /note_links` | связи синхронизируются через `NoteLinkSyncService`; ручной CRUD не используется UI |
| `GET /note_versions` | отдаёт версии **всех** заметок пользователя; UI ходит в `/notes/{id}/versions` |
| `GET /notes/{id}/backlinks` | из UI убран (фаза 14.3); дублирует данные графа |

**Файлы:** `Entity/NoteLink.php`, `Entity/NoteVersion.php`, `Controller/WikiLinkController.php`

- [x] **Вариант A:** убрать `Post`/`Delete`/`GetCollection` у `NoteLink`; оставить только внутреннюю таблицу + graph/backlinks при необходимости
- [x] Убрать или deprecated `GET /note_versions` collection
- [x] `backlinks` — удалить endpoint или оставить с пометкой deprecated (согласовать с фронтом, шаг 4 `frontend_selfreview.md`)

---

## Шаг 12. Дублирование и согласованность паттернов

**Приоритет:** medium / low  
**Коммит:** `refactor(backend): unify ownership and settings validation`

**Проблема:**

| Область | Сейчас | Ожидается |
|---------|--------|-----------|
| Ownership check | вручную в части providers/controllers | единый voter или trait `OwnedByUserTrait` |
| Настройки пользователя | `Assert\Choice` в `User` **и** `UpdateUserSettingsDto::ALLOWED_*` | одни константы (например `UserSettingsResolver` или enum) |
| JSON в контроллерах | `json_decode` + ручная валидация в `AuthController` | DTO + serializer как в `changePassword` |
| Версии | создаются только на `PUT`, не на `PATCH` | задокументировать в `ARCHITECTURE.md` (PATCH — partial metadata) |
| `api_platform.title` | `Hello API Platform` | название приложения |
| `PATCH` заметки | всегда вызывает `noteLinkSync` | skip sync, если менялись только `isFavorite` / `folder` |

- [ ] Вынести `assertOwnedBy(User $owner, User $current): void` в `Security/` или trait для processors
- [ ] Общие константы допустимых значений settings
- [ ] Обновить `ARCHITECTURE.md` (PUT vs PATCH, ownership model)
- [ ] Условный `syncFromContent` в `NoteProcessor` при PATCH без изменения `content`

---

## Шаг 13. Конфигурация и заголовки безопасности

**Приоритет:** medium / low  
**Коммит:** `chore(backend): security headers and api metadata`

**Проблема:**

- `docker/nginx/default.conf` — нет `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Content-Security-Policy` (хотя бы для `/api/docs`)
- JWT без refresh — решение отложено; см. шаг 15 (блокирует фронтенд шаг 5)
- `framework.session: true` при stateless JWT API — лишнее, но не критично
- Нет backend smoke-тестов (запланировано в фазе 20)

**Файлы:** `docker/nginx/default.conf`, `config/packages/api_platform.yaml`, `ARCHITECTURE.md`

- [ ] Добавить базовые security headers в nginx (минимальный набор для demo/prod)
- [ ] Исправить `api_platform.title` / `version`
- [ ] Документировать: refresh token не реализован; TTL JWT — из env Lexik

---

## Шаг 15. JWT refresh (блокирует фронтенд шаг 5)

**Приоритет:** medium  
**Статус:** ⏸ отложен — вернуться после критичных шагов 1–4  
**Коммит:** `feat(backend): implement JWT refresh` **или** `docs(backend): document no-refresh MVP policy`

**Проблема:** в `ARCHITECTURE.md` описан `POST /api/auth/refresh`, но endpoint **не реализован** (`AuthController` его не содержит; Lexik JWT без refresh-bundle). На фронте — мёртвый код: `refreshToken` в `localStorage`, `authApi.refresh`, на 401 сразу logout без retry ([`frontend_selfreview.md` — шаг 5](./frontend_selfreview.md#шаг-5-jwt-refresh-реализовать-или-убрать)).

**Файлы:** `Controller/AuthController.php`, `config/packages/lexik_jwt_authentication.yaml`, `ARCHITECTURE.md`; на фронте после решения — `stores/auth.ts`, `api/auth.ts`, `api/client.ts`

- [ ] **Вариант A:** реализовать refresh (например `gesdinet/jwt-refresh-token-bundle` или свой endpoint + хранение refresh token); login/register возвращают `refreshToken`; TTL и ротация — в env/документации
- [ ] **Вариант B:** зафиксировать MVP без refresh — убрать endpoint из `ARCHITECTURE.md` или пометить «не реализовано»; на фронте шаг 5 вариант B (удалить мёртвый код)
- [ ] Согласовать поведение на 401: единая политика для фронта и бэка
- [ ] После решения — закрыть фронтенд шаг 5

---

## Шаг 14. Мелкие улучшения (low, optional)

**Приоритет:** low  
**Коммит:** по желанию

- [ ] `CleanupTrashCommand` — вынести `30 days` в env/parameter
- [ ] `NoteGraphService::hasUnvisitedNeighbors` — кэш результатов `findLinksForNode` в рамках одного `buildSubgraph` (убрать повторные запросы)
- [ ] `NoteProcessor` на `POST` — группа валидации `note:create` не требует `content`; согласовать с «черновиком» на фронте (пустой content допустим?)
- [ ] `symfony/maker-bundle` — только dev (уже в `require-dev` ✓)

---

## Положительные находки (не требуют правок)

- Collection providers с фильтром по `user`: `FolderCollectionProvider`, `TrashNotesProvider`, `TagCollectionProvider`, `NoteVersionsByNoteProvider`
- `UserChecker` — деактивированные пользователи не проходят login/JWT
- `NoteTextSanitizer` на пути сохранения; wiki-парсер только по UUID
- `NoteLinkSyncService` — одна строка на пару source/target, aliases JSONB, self-link отфильтрован
- `NoteVersionService` — окно консолидации, дедуп по snapshot, лимит 50 версий
- `RestoreNoteProcessor` / `EmptyTrashProcessor` — корректная привязка к текущему пользователю
- `AdminController` — `#[IsGranted('ROLE_ADMIN')]` на классе
- `UserRepository::getUserStatistics` — параметризованный SQL (без инъекций)
- JWT-ключи генерируются при сборке образа, не в git
- Индексы на FK (`user_id`, `folder_id`, `note_links` source/target) в базовой миграции

---

## Порядок коммитов (рекомендуемый)

1. Шаг 1 — ownership / IDOR (**обязательно первым**)
2. Шаг 2 — валидация folder/tags/parent
3. Шаг 3 — sync links после restore
4. Шаг 4 — admin guards
5. Шаг 5 — duplicate email
6. Шаг 6–8 — N+1 (можно одним PR или по отдельности)
7. Шаг 9 — индексы
8. Шаг 10 — dead code
9. Шаг 11 — сузить API
10. Шаг 12–13 — паттерны и конфиг
11. Шаг 15 — JWT refresh (после шагов 1–4; разблокирует фронтенд шаг 5)
12. Шаг 14 — optional

После выполнения шага — отметить `- [x]` в этом файле и кратко зафиксировать в `REPORT.md`.

---

## Доработки после ревью (backlog)

Задачи вне шагов 1–15; выявлены при smoke или ревью фронта.

### Поиск заметок: регистронезависимый

**Источник:** smoke фронта, шаг 6 (`LinkNoteModal` → `notesApi.search`); также `SearchBar` → `NoteSearchController` → `NoteRepository::search`.

**Проблема:** `NoteRepository::search` использует `n.title LIKE :query` и `n.content LIKE :query` без нормализации регистра. `SearchFilter` (`title` => `partial`) на `Note` для `GET /notes?title=` — case-sensitive в PostgreSQL. При этом `findByTitleCaseInsensitive` уже есть для wiki — поведение поиска в UI должно быть согласованным.

**Предлагаемое решение:**
- [ ] `NoteRepository::search`: `LOWER(n.title) LIKE LOWER(:query)` (и content); для PostgreSQL можно `ILIKE`, если зафиксировать СУБД
- [ ] `GET /notes?title=` (после консолидации search API, фронт шаг 7): тот же критерий — кастомный `SearchFilter` или убрать дублирующий путь
- [ ] Smoke: title `Hello World`, запросы `hello`, `HELLO` — находят заметку в SearchBar и модалке ссылок
- [ ] При необходимости — PHPUnit на `NoteRepository::search` (фаза 20)

**Связь:** [`frontend_selfreview.md` — доработки после ревью](./frontend_selfreview.md#доработки-после-ревью-backlog)
