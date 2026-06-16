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

