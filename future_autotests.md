# Автотесты (фаза 20+)

Список сценариев для реализации в PHPUnit (бэкенд) и Vitest (фронтенд).  
**В рамках selfreview тестовый код не пишем** — только фиксируем спецификацию здесь.  
Ручной smoke остаётся в [`for_tests.md`](./for_tests.md).

Формат записи:

```markdown
## [BE|FE] Краткое название

**Источник:** backend_selfreview.md / frontend_selfreview.md, шаг N  
**Тип:** API functional | unit | integration  
**Приоритет:** critical | medium | low

### Подготовка (fixtures / arrange)
- …

### Кейсы
1. … → ожидание HTTP …

### Файлы (предположительно)
- `tests/...`
```

---

## BE IDOR — item-операции только для владельца

**Источник:** `backend_selfreview.md`, шаг 1  
**Тип:** API functional (`WebTestCase` или `ApiTestCase`)  
**Приоритет:** critical  
**Связь:** `UserOwnedResourceItemQueryExtension`, `ResourceOwnershipAssert`

### Зачем автотест вместо ручного smoke

Два пользователя с разными UUID и ресурсами у каждого — типичный arrange для PHPUnit; вручную регистрировать A/B и копировать UUID не нужно.

### Подготовка (fixtures)

1. **Test database** — отдельная БД или `DATABASE_URL` с суффиксом `_test`; перед прогоном `doctrine:database:create` + `doctrine:migrations:migrate` (или `schema:update --force` для изолированного CI).
2. **Пользователи** — создать в fixture/factory, не через UI:
   - `userA` — `user-a@test.local` / пароль `password`
   - `userB` — `user-b@test.local` / пароль `password`
3. **JWT** — для каждого: `POST /api/auth/login` с `{"email","password"}` → `token`; либо `JWTTokenManagerInterface::create($user)` в kernel test без HTTP.
4. **Ресурсы user B** (владелец — только B):
   - заметка `noteB` (активная, `deletedAt = null`)
   - папка `folderB`
   - тег `tagB`
   - опционально: `noteLinkB` между двумя заметками B; `noteVersionB` для `noteB`
5. Сохранить UUID ресурсов B в переменных теста — не хардкодить в репозитории.

### Кейсы — user A + UUID ресурсов user B

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `GET /api/notes/{noteB.id}` + `Authorization: Bearer {tokenA}` | **404** |
| 2 | `PUT /api/notes/{noteB.id}` + body (title/content) + token A | **404** |
| 3 | `PATCH /api/notes/{noteB.id}` + token A | **404** |
| 4 | `DELETE /api/notes/{noteB.id}` + token A | **404** |
| 5 | `GET /api/folders/{folderB.id}` + token A | **404** |
| 6 | `PUT` / `PATCH` / `DELETE /api/folders/{folderB.id}` + token A | **404** |
| 7 | `GET /api/tags/{tagB.id}` + token A | **404** |
| 8 | `PUT` / `DELETE /api/tags/{tagB.id}` + token A | **404** |
| 9 | `GET /api/note_versions/{versionB.id}` + token A (если есть fixture) | **404** |

**Примечание:** публичный CRUD `note_links` и global `GET /api/note_versions` удалены (BE selfreview шаг 11); кейс для `note_links` не актуален.

**Инвариант:** чужой ресурс не отдаётся и не мутируется; на item GET — **404**, не 403.

### Кейсы — владелец B (регрессия + корзина)

| # | Запрос | Ожидание |
|---|--------|----------|
| 11 | `GET /api/notes/{noteB.id}` + token B | **200**, тело заметки |
| 12 | `DELETE /api/notes/{noteB.id}` + token B (soft delete) | **200** / **204** |
| 13 | `GET /api/notes/{noteB.id}` + token B после soft delete | **404** |
| 14 | `DELETE /api/notes/{noteB.id}` + token B (заметка уже в корзине) | успех — permanent delete |
| 15 | `GET /api/folders/{folderB.id}` + token B | **200** |

### Зависимости (установить в фазе 20)

```bash
docker compose exec php composer require --dev symfony/test-pack phpunit/phpunit
```

### Файлы (предположительно)

- `backend/tests/Functional/ResourceOwnershipTest.php` — основной класс
- `backend/tests/Factory/UserFactory.php` — user A/B + JWT helper (опционально)
- `backend/phpunit.xml.dist`
- `backend/.env.test` — `DATABASE_URL` на test DB

### Примечания

- Проверять, что в БД после «чужих» PUT/DELETE данные B **не изменились** (`assertSame` title/content или refresh entity).
- Collection `GET /api/notes` под A не должен содержать `noteB.id` — отдельный кейс или assert в том же классе.

---

## BE owned relations при записи (folder, tags, parent)

**Источник:** `backend_selfreview.md`, шаг 2  
**Тип:** API functional  
**Приоритет:** medium  
**Связь:** `OwnedRelationAssert`, `NoteProcessor`, `FolderProcessor`

### Зачем автотест вместо ручного smoke

Нужны два пользователя с разными folder/tag UUID; в PHPUnit — fixture + JWT, без ручного копирования IRI.

### Подготовка (fixtures)

Те же `userA`, `userB`, JWT, что в «BE IDOR»; дополнительно:

- `folderA`, `folderB` — по одной активной папке у каждого
- `tagA`, `tagB`
- `noteA` — активная заметка user A (для PATCH)
- `folderB_deleted` — папка B в корзине (`deletedAt` set) — для кейса parent

### Кейсы — user A пишет в чужие связи

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `POST /api/notes` + token A, body с `"folder": "/api/folders/{folderB.id}"` | **422**, сообщение про папку |
| 2 | `PATCH /api/notes/{noteA.id}` + token A, `"tags": ["/api/tags/{tagB.id}"]` | **422**, сообщение про тег |
| 3 | `POST /api/folders` + token A, `"parent": "/api/folders/{folderB.id}"` | **422**, сообщение про родительскую папку |
| 4 | `POST /api/folders` + token A, `"parent": "/api/folders/{folderB_deleted.id}"` | **422**, «Родительская папка удалена» |

### Кейсы — регрессия (свои связи)

| # | Запрос | Ожидание |
|---|--------|----------|
| 5 | `POST /api/notes` + token A, `"folder": "/api/folders/{folderA.id}"`, свои tags | **201** |
| 6 | `POST /api/folders` + token A, `"parent": "/api/folders/{folderA.id}"` (дочерняя) | **201** |
| 7 | `PATCH /api/notes/{noteA.id}` + token A, только `isFavorite` | **200**, без регрессии |

### Инварианты

- После отклонённого запроса в БД **нет** привязки note→folderB или note→tagB.
- HTTP **422**, не 403 и не 500.

### Файлы (предположительно)

- `backend/tests/Functional/OwnedRelationValidationTest.php`
- переиспользовать fixtures из `ResourceOwnershipTest` / `UserFactory`

---

## BE сузить API — removed endpoints и регрессия

**Источник:** `backend_selfreview.md`, шаг 11 (вариант A); ручной smoke — [`for_tests.md`](./for_tests.md) «BE Шаг 11»  
**Тип:** API functional  
**Приоритет:** medium  
**Связь:** `NoteLink` без `ApiResource`, `NoteVersion` без global collection, `WikiLinkController` без `backlinks`

### Зачем автотест вместо ручного smoke

Проверка «эндпоинт удалён → 404» и регрессия версий/графа — повторяемый набор HTTP-запросов; покрыть **все пункты** из ручной проверки шага 11 одним test class.

### Подготовка (fixtures)

1. **userA** + JWT (как в «BE IDOR»).
2. **noteA** — активная заметка user A.
3. **noteTarget** — вторая активная заметка user A (для wiki-ссылки).
4. **noteVersion** — хотя бы одна запись в `note_versions` для `noteA` (создать через `PUT` или fixture).
5. После кейса sync: `noteA` с `content`, содержащим `[[{noteTarget.id}]]`.

### Кейсы — удалённые эндпоинты (404)

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `GET /api/note_links` + token A | **404** |
| 2 | `POST /api/note_links` + token A, body `{ sourceNote, targetNote }` | **404** |
| 3 | `GET /api/note_versions` + token A (global collection, без note id) | **404** |
| 4 | `GET /api/notes/{noteA.id}/backlinks` + token A | **404** |

### Кейсы — регрессия (200 + инварианты)

| # | Запрос | Ожидание |
|---|--------|----------|
| 5 | `GET /api/notes/{noteA.id}/versions` + token A | **200**, Hydra collection, ≥1 элемент при наличии версии |
| 6 | `GET /api/notes/{noteA.id}/graph` + token A | **200**, JSON с `nodes` / `edges` (или пустой subgraph) |
| 7 | `PUT /api/notes/{noteA.id}` + token A, content с wiki-ссылкой на `noteTarget` | **200** |
| 8 | `GET /api/notes/{noteA.id}` + token A после кейса 7 | **200**, `linkStats.outgoing >= 1` |
| 9 | `GET /api/notes/{noteA.id}/graph` + token A после кейса 7 | **200**, ребро source→target в subgraph |

### Дополнительно (route list)

- `debug:router` / assert: нет маршрутов `note_links`, `note_backlinks`, global `GET /api/note_versions` (collection).
- `GET /api/note_versions/{id}` для своей версии — **200** (item endpoint сохранён).

### Файлы (предположительно)

- `backend/tests/Functional/NarrowApiSurfaceTest.php`
- переиспользовать fixtures из `ResourceOwnershipTest` / `UserFactory`

---

## FE notes store — изоляция list/detail loading и error

**Источник:** `frontend_selfreview.md`, шаг 10  
**Тип:** unit (Vitest + Pinia)  
**Приоритет:** medium  
**Связь:** `stores/notes.ts`, `DashboardView.vue`, `NoteView.vue`

### Зачем автотест вместо ручного smoke

Сценарии с ошибками API (list vs detail vs mutation) проще воспроизвести через mock `notesApi`, чем имитировать сеть в UI. Проверяется инвариант store, а не вёрстка.

### Подготовка (arrange)

1. **Vitest** — `vitest`, `happy-dom` (или `jsdom`); скрипт `npm test` в `frontend/package.json`.
2. **Pinia** — `setActivePinia(createPinia())` в `beforeEach`; `useNotesStore().reset()` между кейсами.
3. **Mock `@/api/notes`** — `vi.mock` с `getAll`, `getById`, `create`, `update`, `delete`, `toggleFavorite`, `moveToFolder`, `getFavorites`.
4. **Mock `@/stores/trash`** — `useTrashStore().fetchCount` → resolved stub (для `deleteNote`).
5. **Fixtures** — минимальные объекты:
   - `listItem`: `NoteListItem` с `id`, `title`, `folderId: null`, `isFavorite: false`, даты ISO
   - `note`: `Note` с тем же `id`, `content: 'body'`
   - успешный list response: `{ data: [listItem], meta: { currentPage: 1, perPage: 20, total: 1, totalPages: 1 } }`

### Кейсы — list vs detail error

| # | Действие | Arrange | Assert store |
|---|----------|---------|--------------|
| 1 | `fetchNotes()` reject | `getAll` → `reject(new Error('list fail'))` | `listError` не null; `detailError` null; `isLoadingList` false |
| 2 | `fetchNoteById(id)` reject | `getById` → reject | `detailError` не null; `listError` null; `isLoadingDetail` false |
| 3 | Изоляция после list error | сначала кейс 1, затем `getById` → resolve `note` | после успешного detail: `detailError` null, `currentNote` set; **`listError` по-прежнему не null** (list не сбрасывается detail-fetch) |
| 4 | `createNote()` reject | `create` → reject | `detailError` не null; `listError` null |
| 5 | `deleteNote(id)` reject | `delete` → reject | `detailError` не null; `listError` null; `isLoadingDetail` false |

### Кейсы — мутации не пишут в listError

| # | Действие | Arrange | Assert |
|---|----------|---------|--------|
| 6 | `updateNote` reject | store с `currentNote`; `update` → reject | throw; `listError` и `detailError` null |
| 7 | `toggleFavorite` reject | `toggleFavorite` → reject | throw; `listError`, `detailError`, `favoritesError` null |
| 8 | `moveNoteToFolder` reject | `moveToFolder` → reject | throw; `listError`, `detailError` null |

### Кейсы — успешные операции (регрессия state)

| # | Действие | Assert |
|---|----------|--------|
| 9 | `fetchNotes()` resolve | `notes.length === 1`, `listError` null, `isLoadingList` false |
| 10 | `fetchNoteById()` resolve | `currentNote` set, `detailError` null |
| 11 | `createNote()` resolve | `currentNote` set, note в `notes[0]`, `detailError` null |
| 12 | `deleteNote()` resolve | note убрана из `notes`, `currentNote` null при совпадении id |

### Зависимости (установить в фазе 20)

```bash
docker compose exec node npm install -D vitest happy-dom
```

### Файлы (предположительно)

- `frontend/vitest.config.ts` — alias `@`, `environment: 'happy-dom'`
- `frontend/src/stores/__tests__/notes.store.test.ts`
- `frontend/src/stores/__tests__/fixtures/notes.ts` — shared mocks

### Примечания

- Ошибки мутаций в UI показываются через `useAppToast` / `useAutosave` — **не** через store; unit-тест store не покрывает toast, только отсутствие записи в `listError`/`detailError`.
- `fetchPaginatedList` для favorites использует `favoritesError` — отдельный кейс опционально (не входит в шаг 10, но тот же паттерн).
- После реализации тестов — отметить smoke FE шаг 10 в [`for_tests.md`](./for_tests.md) как покрытый автотестами.

---

## BE sync wiki-ссылок после restore версии

**Источник:** `backend_selfreview.md`, шаг 3  
**Тип:** API functional  
**Приоритет:** medium  
**Связь:** `RestoreVersionProcessor`, `NoteLinkSyncService`, `NoteVersionService`

### Подготовка (fixtures)

1. **userA** + JWT.
2. **noteTarget** — вторая активная заметка user A.
3. **noteA** — активная заметка с wiki-ссылкой `[[noteTarget.id]]` и `linkStats.outgoing >= 1`.
4. Создать **versionWithLinks** (PUT noteA) и **versionWithoutLinks** (сохранить версию с пустым content или без ссылок — через fixture или последовательность PUT).

### Кейсы

| # | Действие | Ожидание |
|---|----------|----------|
| 1 | `POST /api/notes/{noteA.id}/versions/{versionWithoutLinks.id}/restore` body `{ "mode": "overwrite" }` | **200**; `GET noteA` — `linkStats.outgoing === 0`; content без ссылок |
| 2 | Restore **versionWithLinks** mode `overwrite` | **200**; `linkStats.outgoing >= 1`; content содержит wiki-ссылку |
| 3 | Restore mode `create_version` | **200**; новая версия в истории; `note_links` соответствуют content |
| 4 | Restore mode `copy` из versionWithLinks | **201**/**200**; **новая** заметка с id ≠ noteA; у новой `linkStats.outgoing >= 1` |

### Инварианты

- После каждого restore — `note_links` соответствуют `content` затронутой заметки, без «хвоста» от состояния до restore.
- Для `copy` sync вызывается для **новой** заметки.

### Файлы (предположительно)

- `backend/tests/Functional/RestoreVersionWikiLinksTest.php`

---

## BE admin guards — self-delete / self-demote

**Источник:** `backend_selfreview.md`, шаг 4  
**Тип:** API functional  
**Приоритет:** medium  
**Связь:** `AdminController`, `UserRepository::countAdmins`

### Подготовка (fixtures)

1. **adminOnly** — единственный пользователь с `ROLE_ADMIN`.
2. **adminA**, **adminB** — два админа (для demote другого).
3. **userRegular** — обычный пользователь (регрессия disable/delete).

JWT для каждого admin.

### Кейсы — единственный админ

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `PATCH /api/admin/users/{adminOnly.id}/disable` + token adminOnly | **400** |
| 2 | `DELETE /api/admin/users/{adminOnly.id}` + token adminOnly | **400** |
| 3 | `PATCH /api/admin/users/{adminOnly.id}/demote` + token adminOnly | **409** |

### Кейсы — два админа

| # | Запрос | Ожидание |
|---|--------|----------|
| 4 | demote **adminB** token adminA | **200**; у adminB нет `ROLE_ADMIN` |
| 5 | demote последнего adminA (когда adminB уже demoted) | **409** |

### Кейсы — регрессия

| # | Запрос | Ожидание |
|---|--------|----------|
| 6 | disable/delete **userRegular** token adminA | **200** |
| 7 | promote userRegular → admin | **200** |

### Файлы (предположительно)

- `backend/tests/Functional/AdminSelfManagementTest.php`

---

## BE register — дубликат email

**Источник:** `backend_selfreview.md`, шаг 5  
**Тип:** API functional  
**Приоритет:** medium  
**Связь:** `AuthController::register`

### Кейсы

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `POST /api/auth/register` новый email | **201**; `token`, `user` в теле |
| 2 | Повторный register с тем же email | **409**; `{"error":"Email уже занят"}` |
| 3 | Register с невалидным email | **400**; `errors`, не 409 |

### Файлы (предположительно)

- `backend/tests/Functional/AuthRegisterTest.php`

---

## BE batch admin user statistics

**Источник:** `backend_selfreview.md`, шаг 6  
**Тип:** unit / integration  
**Приоритет:** medium  
**Связь:** `UserRepository::getUsersStatisticsBatch`, `AdminController::listUsers`

### Unit — `getUsersStatisticsBatch`

| # | Arrange | Assert |
|---|---------|--------|
| 1 | `[]` | `[]` |
| 2 | один userId с notes/folders/tags | ключ userId; counts совпадают с `getUserStatistics` |
| 3 | несколько userIds | все ключи; batch = по одному вызову `getUserStatistics` |

### Functional (опционально)

| # | Запрос | Assert |
|---|--------|--------|
| 4 | `GET /api/admin/users?perPage=20` | `statistics` у каждого; Doctrine query count ≤ ожидаемого (~5) |

### Файлы (предположительно)

- `backend/tests/Unit/Repository/UserRepositoryStatisticsTest.php`
- `backend/tests/Functional/AdminUsersListTest.php` (опционально)

---

## BE batch wiki title resolution в list preview

**Источник:** `backend_selfreview.md`, шаг 7  
**Тип:** unit + API functional  
**Приоритет:** medium  
**Связь:** `NotePreviewService`, `NoteListCollectionNormalizer`, `NoteSearchController`

### Unit — `NotePreviewService::prefetchWikiTitlesForNotes`

| # | Arrange | Assert |
|---|---------|--------|
| 1 | 3 notes с `[[sameTargetId]]` без alias | один вызов `findActiveByIdsForUser` с dedup id |
| 2 | notes с пустым content | `[]`, без SQL |
| 3 | только `[[id\|Alias]]` (alias задан) | `[]`, без SQL на target id |
| 4 | `buildPreview(content, user, titlesById)` | title подставлен, не UUID |

### Functional — `contentPreview` в API

**Подготовка:** `noteTarget` title «Target Note»; 2+ notes с `See [[targetId]]`.

| # | Запрос | Ожидание |
|---|--------|----------|
| 5 | `GET /api/notes` | каждый `contentPreview` содержит «Target Note», не UUID |
| 6 | `GET /api/notes/search?q=…` | то же в `data[]` |
| 7 | soft-delete note с wiki-link → `GET /api/notes/trash` | preview с заголовками |
| 8 | `[[uuid\|Alias]]` | preview содержит «Alias» |

### Functional — SQL count (опционально)

- Profiler / Doctrine logger: на list ~20 notes с wiki-links — **1** (или 0) запрос `findActiveByIdsForUser`.

### Файлы (предположительно)

- `backend/tests/Unit/Service/NotePreviewServiceTest.php`
- `backend/tests/Functional/NoteListPreviewTest.php`

---

## BE combine note read metadata queries

**Источник:** `backend_selfreview.md`, шаг 8  
**Тип:** unit + API functional  
**Приоритет:** medium  
**Связь:** `NoteLinkRepository::getNoteReadMetadata`, `NoteReadNormalizer`

### Unit — `getNoteReadMetadata`

| # | Arrange | Assert |
|---|---------|--------|
| 1 | note без links и versions | `{ incoming: 0, outgoing: 0, versionCount: 0 }` |
| 2 | note с outgoing link на активную заметку | `outgoing >= 1` |
| 3 | note с incoming link | `incoming >= 1` |
| 4 | note с N версиями в `note_versions` | `versionCount === N` |

### Functional

| # | Запрос | Ожидание |
|---|--------|----------|
| 5 | `GET /api/notes/{id}` | поля `linkStats`, `versionCount`; значения совпадают с unit |
| 6 | Doctrine: один SQL с subselect для metadata (не 3 COUNT) | опционально через logger |

### Файлы (предположительно)

- `backend/tests/Unit/Repository/NoteLinkRepositoryMetadataTest.php`
- `backend/tests/Functional/NoteReadMetadataTest.php`

---

## BE индексы для списков заметок

**Источник:** `backend_selfreview.md`, шаг 9  
**Тип:** integration (optional)  
**Приоритет:** low  
**Связь:** миграция `Version20260616120000`, `NoteRepository`

### Кейсы

| # | Действие | Ожидание |
|---|----------|----------|
| 1 | `doctrine:migrations:migrate` up | индексы `notes_user_active_updated_idx`, `notes_user_favorite_active_updated_idx` существуют |
| 2 | migrate down/up | без ошибок |
| 3 | `GET /api/notes`, `GET /api/notes?isFavorite=true` | **200**, регрессия формата |
| 4 | `EXPLAIN` list-запроса на test DB с данными | Index Scan / Bitmap по partial index (optional CI) |

### Примечания

- Functional smoke list/favorites достаточен для MVP; EXPLAIN — только при нагрузочном CI.
- `LIKE` search без full-text индекса — не тестировать на perf, только регрессия `GET /api/notes/search`.

### Файлы (предположительно)

- `backend/tests/Integration/Migrations/NotesIndexesMigrationTest.php`

---

## BE PATCH sync и settings validation

**Источник:** `backend_selfreview.md`, шаг 12  
**Тип:** API functional  
**Приоритет:** medium  
**Связь:** `NoteProcessor::shouldSyncNoteLinks`, `UserSettingOptions`

### Подготовка

- **noteA** с wiki-ссылкой и `linkStats.outgoing >= 1`
- Зафиксировать count строк в `note_links` до PATCH

### Кейсы — conditional sync

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `PATCH /api/notes/{noteA.id}` только `{ "isFavorite": true }` | **200**; count `note_links` **не изменился** |
| 2 | `PATCH` только `{ "folder": "/api/folders/{folderA.id}" }` | **200**; `note_links` без изменений |
| 3 | `PUT` с изменением `content` (добавить/убрать wiki-link) | **200**; `linkStats` / `note_links` обновлены |

### Кейсы — settings

| # | Запрос | Ожидание |
|---|--------|----------|
| 4 | `PATCH /api/auth/settings` `{ "autosaveDelaySeconds": 7 }` (недопустимо) | **422** |
| 5 | `{ "autosaveDelaySeconds": 5 }` (из `UserSettingOptions`) | **200** |

### Файлы (предположительно)

- `backend/tests/Functional/NotePatchSyncTest.php`
- `backend/tests/Functional/UserSettingsValidationTest.php`

---

## BE security headers и API metadata

**Источник:** `backend_selfreview.md`, шаг 13  
**Тип:** API functional  
**Приоритет:** low  
**Связь:** `docker/nginx/default.conf`, `api_platform.yaml`

### Кейсы

| # | Запрос | Assert headers / body |
|---|--------|----------------------|
| 1 | `GET /api/auth/me` (с JWT) | `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin` |
| 2 | `GET /api/docs` | OpenAPI title «Персональная база знаний API» (не «Hello API Platform») |

### Файлы (предположительно)

- `backend/tests/Functional/SecurityHeadersTest.php`

---

## BE JWT refresh flow

**Источник:** `backend_selfreview.md`, шаг 15  
**Тип:** API functional  
**Приоритет:** medium  
**Связь:** `AuthController`, `gesdinet/jwt-refresh-token-bundle`

### Кейсы

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `POST /api/auth/login` | **200**; `token`, `refreshToken`, `user` |
| 2 | `POST /api/auth/refresh` `{ "refreshToken": "<from login>" }` | **200**; новые `token`, `refreshToken` |
| 3 | Повторный refresh со **старым** refresh token | **401** (single-use ротация) |
| 4 | refresh с невалидным token | **401** |
| 5 | `GET /api/auth/me` с новым access после refresh | **200** |

### Файлы (предположительно)

- `backend/tests/Functional/JwtRefreshTest.php`

---

## BE мелкие улучшения — trash, graph batch, content on create (шаг 14)

**Источник:** `backend_selfreview.md`, шаг 14; smoke — [`for_tests.md`](./for_tests.md) «BE Шаг 14»  
**Тип:** API functional + unit (graph) + integration (console)  
**Приоритет:** low  
**Связь:** `CleanupTrashCommand`, `NoteGraphService`, `NoteLinkRepository::findLinksForNodes`, `Note` validation `note:create`

### Подготовка (fixtures)

1. **Test database** — как в «BE IDOR» (`APP_ENV=test`, `dbname_suffix: _test`).
2. **Пользователь** — `userA` + JWT (login или `JWTTokenManagerInterface::create`).
3. **Граф (кейсы graph):**
   - `noteRoot`, `noteLinked`, `noteFar` — активные заметки user A;
   - `noteLink1`: source `noteRoot` → target `noteLinked`;
   - `noteLink2`: source `noteLinked` → target `noteFar` (цепочка для `depth=2`).
4. **Корзина (кейс cleanup):**
   - `noteTrashedOld` — `deletedAt` = now − (`TRASH_RETENTION_DAYS` + 1) days;
   - `noteTrashedRecent` — `deletedAt` = now − 1 day.

### Кейсы — API graph (регрессия + batch)

| # | Запрос / действие | Ожидание |
|---|-------------------|----------|
| 1 | `GET /api/notes/{noteRoot.id}/graph?depth=2&direction=both` + token A | **200**; JSON: `nodes`, `edges`, `truncated`, `frontierNodeIds`; в `nodes` есть id root, linked, far (или subset при truncation) |
| 2 | То же | в `edges` есть ребро root→linked и linked→far |
| 3 | Unit/integration: `NoteGraphService::buildSubgraph` с mock/spy `NoteLinkRepository` | `findLinksForNodes` вызван **не более `depth + 1` раз** (batch по уровням BFS + финальный batch), **не** N× `findLinksForNode` на узел |

### Кейсы — POST /notes, обязательный content

| # | Запрос | Ожидание |
|---|--------|----------|
| 4 | `POST /api/notes` `{ "title": "Test" }` (без `content`) + token A | **422**; сообщение «Содержимое не может быть пустым» |
| 5 | `POST /api/notes` `{ "title": "Test", "content": "" }` + token A | **422** (пустая строка) |
| 6 | `POST /api/notes` `{ "title": "Test", "content": "hello" }` + token A | **201**; в ответе `id`, `title`, `content` |

### Кейсы — cleanup trash (console)

| # | Действие | Ожидание |
|---|----------|--------|
| 7 | `app:cleanup-trash` (KernelTestCase, default `TRASH_RETENTION_DAYS=30`) | exit **0**; в output «30 day(s)» (или значение из env); `noteTrashedOld` удалена из БД |
| 8 | После cleanup | `noteTrashedRecent` **осталась** в БД (`deletedAt IS NOT NULL`) |

### Зависимости

- PHPUnit + `symfony/test-pack` (см. «BE IDOR»); прогон: `docker compose exec php php bin/phpunit`.

### Файлы (предположительно)

- `backend/tests/Functional/NoteGraphApiTest.php` — кейсы 1–2
- `backend/tests/Functional/NoteCreateValidationTest.php` — кейсы 4–6
- `backend/tests/Integration/Command/CleanupTrashCommandTest.php` — кейсы 7–8
- `backend/tests/Unit/Service/NoteGraphServiceBatchTest.php` — кейс 3
- `backend/tests/Functional/ApiTestCase.php` — общий JWT + fixtures (если ещё нет)

---

## FE черновик — POST только с непустым content (шаг 14)

**Источник:** `backend_selfreview.md`, шаг 14; smoke — [`for_tests.md`](./for_tests.md) «BE Шаг 14» (пункт «Фронт: новая заметка…»)  
**Тип:** unit / component (Vitest)  
**Приоритет:** low  
**Связь:** `NoteView.vue`, `hasNoteBody`, `useCreateNote`, `notesStore.createNote`

### Кейсы

| # | Сценарий | Assert |
|---|----------|--------|
| 1 | `hasNoteBody('')` / `hasNoteBody('   ')` | `false` |
| 2 | `hasNoteBody('hello')` | `true` |
| 3 | `saveNoteIfChanged` / `persistDraftNote` при пустом content | `notesApi.create` **не вызван** |
| 4 | После ввода текста и autosave | ровно **один** `POST /notes` с непустым `content`; затем `router.replace` на `/note/:id` |

### Файлы (предположительно)

- `frontend/src/utils/__tests__/note.test.ts` — кейсы 1–2
- `frontend/src/views/__tests__/NoteView.draft.test.ts` — кейсы 3–4

---

## FE XSS — escapeHtml и highlightMatch

**Источник:** `frontend_selfreview.md`, шаг 1  
**Тип:** unit (Vitest)  
**Приоритет:** medium (security)  
**Связь:** `SearchBar.vue`, `utils/` (escapeHtml, highlightMatch)

### Кейсы

| # | Input | Assert |
|---|-------|--------|
| 1 | `escapeHtml('<img src=x onerror=alert(1)>')` | строка без исполняемых тегов; угловые скобки escaped |
| 2 | `highlightMatch('<script>alert(1)</script>', 'script')` | результат без `<script>`; совпадение в `<mark>` |
| 3 | title с XSS, query без совпадения | output экранирован целиком |

### Файлы (предположительно)

- `frontend/src/utils/__tests__/escapeHtml.test.ts`
- `frontend/src/utils/__tests__/highlightMatch.test.ts`

---

## FE sanitize markdown HTML в preview

**Источник:** `frontend_selfreview.md`, шаг 2  
**Тип:** unit (Vitest)  
**Приоритет:** medium (security)  
**Связь:** `MarkdownPreview.vue`, `utils/sanitizeText.ts` / DOMPurify

### Кейсы

| # | Input markdown | Assert |
|---|----------------|--------|
| 1 | `<script>alert(1)</script>` | нет `<script>` в rendered HTML |
| 2 | `<img src=x onerror=alert(1)>` | атрибут onerror удалён или тег stripped |
| 3 | обычный markdown `# Title` | рендер без регрессии |

### Примечания

- E2E с реальным Milkdown — опционально; unit на sanitize-слой достаточен для MVP.

### Файлы (предположительно)

- `frontend/src/utils/__tests__/sanitizeMarkdownHtml.test.ts`

---

## FE JWT refresh interceptor

**Источник:** `frontend_selfreview.md`, шаг 5  
**Тип:** unit (Vitest)  
**Приоритет:** medium  
**Связь:** `api/client.ts`, `stores/auth.ts`, `api/auth.ts`

### Кейсы (mock fetch/axios)

| # | Сценарий | Assert |
|---|----------|--------|
| 1 | 401 → refresh **200** → retry original | один вызов refresh; original запрос повторён с новым token |
| 2 | несколько параллельных 401 | один refresh (mutex/queue), все retry успешны |
| 3 | 401 → refresh **401** | tokens cleared; redirect `/login` |
| 4 | нет refreshToken в storage | сразу logout, без вызова refresh |

### Файлы (предположительно)

- `frontend/src/api/__tests__/client.refresh.test.ts`

---

## FE parseHydraCollection

**Источник:** `frontend_selfreview.md`, шаг 6  
**Тип:** unit (Vitest)  
**Приоритет:** low  
**Связь:** `utils/hydra.ts`

### Кейсы

| # | Input response | Assert |
|---|----------------|--------|
| 1 | `{ 'hydra:member': [a], 'hydra:totalItems': 1 }` | `{ data: [a], total: 1 }` |
| 2 | `{ member: [b] }` | `{ data: [b], total: … }` |
| 3 | голый массив | `{ data: array, total: length }` |
| 4 | пустая коллекция, totalItems = 0 | `{ data: [], total: 0 }` |

### Файлы (предположительно)

- `frontend/src/utils/__tests__/hydra.test.ts`

---

## FE LinkNoteModal — searchByTitle

**Источник:** `frontend_selfreview.md`, шаг 7  
**Тип:** unit (Vitest + component)  
**Приоритет:** low  
**Связь:** `LinkNoteModal.vue`, `api/search.ts`

### Кейсы

| # | Действие | Assert |
|---|----------|--------|
| 1 | ввод query в модалке | вызван `searchApi.searchByTitle`, не `searchApi.search` |
| 2 | `excludeNoteId` prop | передан в API-вызов |

### Файлы (предположительно)

- `frontend/src/components/.../__tests__/LinkNoteModal.test.ts`

---

## FE fetchPaginatedList dedup

**Источник:** `frontend_selfreview.md`, шаг 8  
**Тип:** unit (Vitest + Pinia)  
**Приоритет:** low  
**Связь:** `stores/notes.ts` (internal `fetchPaginatedList`)

### Кейсы

| # | Сценарий | Assert |
|---|----------|--------|
| 1 | два одновременных `fetchNotes()` | один in-flight HTTP |
| 2 | `loadMore` append | `notes` = page1 + page2; meta обновлена |
| 3 | смена criteriaKey во время load | результат старого запроса игнорируется |

### Файлы (предположительно)

- `frontend/src/stores/__tests__/notes.paginated.test.ts`

---

## FE shared utils — filters, folders, dates

**Источник:** `frontend_selfreview.md`, шаг 9  
**Тип:** unit (Vitest)  
**Приоритет:** low  
**Связь:** `utils/filters.ts`, `folders.ts`, `date.ts`, `pluralize.ts`

### Кейсы

| # | Функция | Assert |
|---|---------|--------|
| 1 | `buildFilterCriteriaKey(null, ['b','a'])` | стабильный ключ независимо от порядка tag ids |
| 2 | `findFolderInTree` | находит вложенную папку |
| 3 | `formatCardDate` | «Сегодня» / «Вчера» / locale для старых дат |
| 4 | `pluralizeNotes(1/2/5)` | корректное склонение ru |

### Файлы (предположительно)

- `frontend/src/utils/__tests__/filters.test.ts`
- `frontend/src/utils/__tests__/folders.test.ts`
- `frontend/src/utils/__tests__/date.test.ts`
- `frontend/src/utils/__tests__/pluralize.test.ts`

---

## BE/FE регистронезависимый поиск (backlog)

**Источник:** «Доработки после ревью» в `frontend_selfreview.md` / `backend_selfreview.md`  
**Тип:** API functional + unit (FE highlight опционально)  
**Приоритет:** medium (backlog, вне шагов 1–15)

### BE — подготовка

- Заметка title `Hello World`, content без слова hello.

### BE кейсы

| # | Запрос | Ожидание |
|---|--------|----------|
| 1 | `GET /api/notes/search?q=hello` | note найдена |
| 2 | `GET /api/notes/search?q=HELLO` | note найдена |
| 3 | `GET /api/notes?title=hello` | note найдена (LinkNoteModal path) |
| 4 | Unit `NoteRepository::search` | `LOWER`/`ILIKE` в SQL |

### FE кейсы (после BE)

| # | UI | Ожидание |
|---|-----|----------|
| 5 | SearchBar query `hello` | находит «Hello World» |
| 6 | LinkNoteModal query `hello` | находит по title |

### Файлы (предположительно)

- `backend/tests/Functional/NoteSearchCaseInsensitiveTest.php`
- `backend/tests/Unit/Repository/NoteRepositorySearchTest.php`

---

## Сводка: покрытие selfreview → future_autotests

| Шаг | Секция в этом файле |
|-----|---------------------|
| BE 1 | BE IDOR |
| BE 2 | BE owned relations |
| BE 3 | BE sync wiki-ссылок после restore |
| BE 4 | BE admin guards |
| BE 5 | BE register duplicate email |
| BE 6 | BE batch admin statistics |
| BE 7 | BE batch wiki title preview |
| BE 8 | BE combine note read metadata |
| BE 9 | BE индексы |
| BE 11 | BE сузить API |
| BE 12 | BE PATCH sync и settings |
| BE 13 | BE security headers |
| BE 14 | BE мелкие улучшения (trash, graph batch, content on create); FE черновик POST |
| BE 15 | BE JWT refresh |
| FE 1 | FE XSS escapeHtml |
| FE 2 | FE sanitize markdown |
| FE 5 | FE JWT refresh interceptor |
| FE 6 | FE parseHydraCollection |
| FE 7 | FE LinkNoteModal searchByTitle |
| FE 8 | FE fetchPaginatedList |
| FE 9 | FE shared utils |
| FE 10 | FE notes store loading/error |
| Backlog | BE/FE case-insensitive search |

**Не покрываем тестами (по selfreview):** BE 10 (dead code), FE 3–4 (deps/chore), FE 11–14 (refactor follow-up). BE 14 и FE черновик — см. секции выше (реализация в фазе 20).

