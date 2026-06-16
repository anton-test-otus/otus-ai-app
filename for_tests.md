# Сценарии для тестов (self-review)

Ручные smoke и идеи для автотестов по шагам `frontend_selfreview.md` / `backend_selfreview.md`.  
**В рамках код-ревью тесты не пишем** — только фиксируем, что и как проверить (см. `.cursor/rules/selfreview-workflow.mdc`).

Формат записи:

```markdown
## [FE|BE] Шаг N — краткое название

**Источник:** frontend_selfreview.md / backend_selfreview.md, шаг N

### Smoke (ручная проверка)
- [ ] шаг 1
- [ ] шаг 2

**Ожидание:** …

### Автотесты (позже, фаза 20+)
- …
```

---

## FE Шаг 1 — XSS в SearchBar

**Источник:** `frontend_selfreview.md`, шаг 1

### Smoke
- [x] Создать заметку с title: `<img src=x onerror=alert(1)>`
- [x] Открыть поиск, ввести запрос без совпадения с title
- [x] Открыть полный поиск (Enter)

**Ожидание:** в результатах title отображается как текст, `alert` не срабатывает.

---

## FE Шаг 2 — raw HTML в markdown preview

**Источник:** `frontend_selfreview.md`, шаг 2

### Smoke
- [x] В контент заметки вставить:
  ```markdown
  <script>alert(1)</script>
  <img src=x onerror=alert(1)>
  ```
- [x] Открыть preview и режим редактора
- [x] Открыть печать (`NotePrintView`)

**Ожидание:** разметка видна как текст, скрипты не выполняются.

### Автотесты (позже)
- E2E: markdown с raw HTML в preview — нет исполняемых узлов в DOM.

---

## FE Шаг 6 — parseHydraCollection

**Источник:** `frontend_selfreview.md`, шаг 6

### Smoke
- [ ] Dashboard — список заметок, infinite scroll
- [ ] `/favorites` — избранные, подгрузка
- [ ] Корзина `/trash` — список, восстановление
- [ ] Сайдбар — теги и дерево папок
- [ ] История версий — модалка, список версий
- [ ] Wiki-ссылка → модалка выбора заметки — результаты по title (см. FE шаг 7)

**Ожидание:** все списки загружаются без ошибок; пагинация и пустые состояния как до рефакторинга.

**Находка (backlog):** поиск в модалке wiki-ссылок регистрозависимый — см. «Доработки после ревью» в `frontend_selfreview.md` / `backend_selfreview.md`.

### Автотесты (позже)
- Unit: `parseHydraCollection` — `hydra:member`, `member`, голый массив, `totalItems = 0`.

---

## FE Шаг 7 — единый API поиска заметок

**Источник:** `frontend_selfreview.md`, шаг 7

### Smoke
- [x] **SearchBar** — полнотекст по title и content (`GET /notes/search?q=…`)
- [x] **Wiki-ссылка** (`Ctrl+Alt+W` или кнопка) → модалка — только по **title** (`GET /notes?title=…` через `searchApi.searchByTitle`)
- [x] В модалке: слово есть только в content заметки — **не** находится; то же слово в title — находится
- [x] В модалке: исключение текущей заметки (`excludeNoteId`) работает
- [x] Dashboard с фильтром тегов — `notesApi.filter` → `/notes/search` без `q` (без регрессии)

**Ожидание:** SearchBar — `searchApi.search`; wiki-модалка — `searchApi.searchByTitle`; `GET /notes?title=` только из модалки.

**Находка (backlog):** регистронезависимый поиск — см. «Доработки после ревью» в selfreview-файлах.

### Автотесты (позже)
- Unit: мок `searchApi` в тесте `LinkNoteModal` (фаза 20).

---

## FE Шаг 8 — дедупликация paginated fetch

**Источник:** `frontend_selfreview.md`, шаг 8

### Smoke
- [x] **Dashboard** — первая загрузка списка заметок; смена папки / тегов сбрасывает список и грузит заново
- [x] **Dashboard** — прокрутка вниз подгружает следующую страницу (infinite scroll); **без скролла** вторая страница не грузится
- [x] **Dashboard** — быстрая смена папки во время load more не смешивает результаты (criteriaKey)
- [x] **`/favorites`** — первая загрузка избранного; infinite scroll подгружает следующую порцию
- [x] **`/favorites`** — ошибка при load more показывает toast, список уже загруженных остаётся

**Ожидание:** поведение как до рефакторинга; публичный API store (`fetchNotes`, `fetchFavorites`, `loadMore*`) без изменений для views.

### Автотесты (позже)
- Unit: `fetchPaginatedList` — dedup in-flight, append merge, criteriaKey guard (фаза 20).

---

## FE Шаг 9 — общие утилиты (фильтры, папки, даты, auth)

**Источник:** `frontend_selfreview.md`, шаг 9

### Smoke
- [x] **Dashboard** — карточки заметок: даты «Сегодня» / «Вчера» / «N дн. назад» / locale; подзаголовок «N заметок» с корректным склонением
- [x] **`/favorites`** — те же форматы дат на карточках; подзаголовок «N заметок»
- [x] **Корзина** — «Удалена сегодня» / «вчера» / «N дн. назад» (строчные относительные метки в середине фразы)
- [x] **Dropdown папок** (NoteView, перемещение заметки) — иерархия и отступы как раньше; parent-only режим не ломается
- [x] **Фильтр по тегам + папка** — смена фильтров перезагружает списки notes/tags без смешивания (общий `buildFilterCriteriaKey`)
- [x] **Login / Register** — успешный вход сохраняет token, пользователь авторизован (регрессия `applyAuthResponse`)

**Ожидание:** поведение UI без изменений; только внутренний рефакторинг.

### Автотесты (позже)
- Unit: `buildFilterCriteriaKey`, `formatCardDate`, `pluralizeNotes`, `findFolderInTree` (фаза 20).

---

## FE Шаг 10 — разделение loading/error в notes store

**Источник:** `frontend_selfreview.md`, шаг 10

**Статус проверки:** ручной smoke **не обязателен** — сценарии собраны для автотестов фазы 20; см. [`future_autotests.md`](./future_autotests.md) («FE notes store — изоляция list/detail loading и error»). Код тестов ещё не реализован.

### Smoke (ручной, опционально)
- [ ] **Dashboard** — первая загрузка списка; ошибка сети на list → `ErrorState` на dashboard, без блокировки других экранов
- [ ] **NoteView** — открыть существующую заметку; неверный UUID → `ErrorState` только в NoteView, dashboard без ошибки
- [ ] **NoteView** — autosave / редактирование: ошибка сохранения → toast, dashboard `listError` пуст
- [ ] **Dashboard / Favorites** — toggle favorite с ошибкой API → toast, без `listError` / `favoritesError` от мутации
- [ ] **Dashboard** — удаление заметки с карточки: toast при ошибке, list не показывает detail-ошибку
- [ ] **NoteView** — удаление заметки: при ошибке toast, без перехода на dashboard

**Ожидание:** list- и detail-состояния изолированы; мутации не пишут в `listError`.

### Автотесты (фаза 20+)

Спецификация: [`future_autotests.md`](./future_autotests.md) — «FE notes store — изоляция list/detail loading и error». После реализации Vitest — закрыть проверку шага 10 без ручного smoke.

---

## BE Шаг 1 — IDOR / ownership на item-операциях

**Источник:** `backend_selfreview.md`, шаг 1

### Smoke (ручная проверка)
- [ ] Под одним пользователем: `GET` / `PUT` / `PATCH` / `DELETE` своей заметки, папки, тега — без регрессии
- [ ] Soft-delete заметки → `GET` той же заметки → **404**; из корзины `DELETE` (permanent) — успех

**Ожидание:** собственные CRUD и корзина работают как до фикса.

### Автотесты (фаза 20+)

Сценарий с **двумя пользователями и чужими UUID** — в [`future_autotests.md`](./future_autotests.md) («BE IDOR — item-операции только для владельца»). Ручная регистрация A/B не требуется.

---

## BE Шаг 2 — валидация связей folder / tags / parent

**Источник:** `backend_selfreview.md`, шаг 2  
**Ручной smoke:** не выполнялся — сценарии в [`future_autotests.md`](./future_autotests.md) («BE owned relations»).

### Smoke (ручная проверка)
- [ ] Под пользователем A: `POST`/`PATCH` своей заметки с `folder` = IRI папки пользователя B → **422**, заметка не привязана к чужой папке
- [ ] Под A: `POST`/`PATCH` заметки с `tags`, содержащим IRI тега B → **422**
- [ ] Под A: `POST`/`PATCH` папки с `parent` = IRI папки B → **422**
- [ ] Под A: `parent` = IRI своей папки из корзины (`deletedAt`) → **422** «Родительская папка удалена»
- [ ] Свои folder/tags/parent и CRUD без изменений payload → без регрессии

**Ожидание:** понятное сообщение в теле ответа (422), не silent ignore и не 500.

### Автотесты (фаза 20+)

См. [`future_autotests.md`](./future_autotests.md) — «BE owned relations при записи».

---

## BE Шаг 3 — sync wiki-ссылок после restore версии

**Источник:** `backend_selfreview.md`, шаг 3

### Smoke (ручная проверка)
- [x] Заметка A с wiki-ссылками на B и C — в БД/`GET /notes/{id}` есть исходящие связи (`linkStats.outgoing` > 0 или граф)
- [x] В истории версий выбрать **старую версию без ссылок** → restore **overwrite** (или `create_version`)
- [x] Повторить проверку связей: исходящие из A соответствуют **восстановленному** `content` (пусто, если ссылок не было)
- [x] Restore **copy** из версии **со ссылками** → у **новой** заметки появились исходящие связи по content копии

**Ожидание:** `note_links` синхронизированы с `content` после restore; граф и `linkStats` не показывают «хвост» от состояния до restore.

### Автотесты (фаза 20+)
- Restore overwrite/create_version/copy — mock или fixture: content меняется → `syncFromContent` вызван для правильной заметки.

---

## BE Шаг 4 — защита админки от self-delete / self-demote

**Источник:** `backend_selfreview.md`, шаг 4

### Smoke (ручная проверка)
- [x] Единственный админ: `PATCH /api/admin/users/{ownId}/disable` → **400**, аккаунт остаётся активным
- [x] Единственный админ: `DELETE /api/admin/users/{ownId}` → **400**, аккаунт не удалён
- [x] Единственный админ: `PATCH /api/admin/users/{ownId}/demote` (через API) → **409**, `ROLE_ADMIN` сохранена
- [x] Два админа: demote **другого** → **200**, у цели снята роль; demote последнего оставшегося → **409**
- [x] Обычные операции над **другими** пользователями (disable/delete/promote/demote) — без регрессии

**Ожидание:** понятное сообщение в теле ответа; нельзя случайно заблокировать или лишить админки единственного администратора.

### Автотесты (фаза 20+)
- Admin guards: self disable/delete → 400; last admin demote → 409.

---

## BE Шаг 5 — дубликат email при регистрации

**Источник:** `backend_selfreview.md`, шаг 5

### Smoke (ручная проверка)
- [x] `POST /api/auth/register` с новым email → **201**, тело с `token` и `user`
- [x] Повторный `POST /api/auth/register` с тем же email (другой пароль допустим) → **409**, `{"error":"Email уже занят"}`, без 500 в логах
- [x] Невалидный email → по-прежнему **400** с `errors`, не 409

**Ожидание:** предсказуемый конфликт при повторной регистрации; первая регистрация без регрессии.

### Автотесты (фаза 20+)
- Register duplicate email → 409; first register → 201.

---

## BE Шаг 6 — batch статистика пользователей в админке

**Источник:** `backend_selfreview.md`, шаг 6

### Smoke (ручная проверка)
- [x] `GET /api/admin/users?perPage=20` — список загружается, `statistics` у каждого пользователя на месте
- [x] Для одного id из списка: `GET /api/admin/users/{id}` — те же `notesCount`, `foldersCount`, `tagsCount`, `lastActivity`, `storageSize`, что в списке
- [x] Symfony profiler / Doctrine: на list ~5 SQL (список + count + 3 batch), на details ~3 SQL (не 5)
- [x] Поиск `?q=...` — статистика без регрессии

**Ожидание:** меньше запросов к БД; формат JSON ответа не изменился.

### Автотесты (фаза 20+)
- `getUsersStatisticsBatch` — пустой массив, один id, несколько id; совпадение с прежним `getUserStatistics`.

---

## BE Шаг 7 — batch wiki title resolution в list preview

**Источник:** `backend_selfreview.md`, шаг 7

### Подготовка
- Заметка «Target Note» (запомнить UUID)
- Несколько заметок с `content`: `See [[uuid-target]]` (без alias)

### Smoke (ручная проверка)
- [ ] Dashboard / список в папке — `contentPreview` показывает «Target Note», не UUID
- [ ] `GET /api/notes/search?q=…` — то же в `data[].contentPreview`
- [ ] `GET /api/notes/trash` — заметки с wiki-ссылками: preview с заголовками
- [ ] Ссылка `[[uuid|Alias]]` — в preview alias, без лишнего SQL на этот uuid
- [ ] Symfony profiler на странице ~20 заметок с wiki-ссылками — **1** (или 0) запрос `findActiveByIdsForUser`, не N

**Ожидание:** формат JSON не изменился; preview как до оптимизации; меньше SQL на list/search/trash.

### Автотесты (фаза 20+)
- `NotePreviewService::prefetchWikiTitlesForNotes` — дедуп id по странице; пустой content; только aliased links → без SQL.

---

## BE Шаг 8 — combine note read metadata queries

**Источник:** `backend_selfreview.md`, шаг 8

### Подготовка
- Заметка с wiki-ссылками (incoming/outgoing > 0) и историей версий (например из demo seed, `isFavorite` с версиями)

### Smoke (ручная проверка)
- [ ] `GET /api/notes/{id}` — поля `linkStats.incoming`, `linkStats.outgoing`, `versionCount` на месте
- [ ] Значения совпадают с UI: кнопка «Связанные заметки» видна при `incoming > 0 OR outgoing > 0`; «История версий» — при `versionCount > 0`
- [ ] Заметка без ссылок и версий — `{ incoming: 0, outgoing: 0, versionCount: 0 }`
- [ ] Symfony profiler / Doctrine на `GET /api/notes/{id}` — **1** SQL с subselect для metadata (не 3 отдельных COUNT)

**Ожидание:** формат JSON `note:read` не изменился; меньше SQL на открытие заметки.

### Автотесты (фаза 20+)
- `NoteLinkRepository::getNoteReadMetadata` — note без links/versions; note с incoming/outgoing; note с версиями.

---

## BE Шаг 9 — индексы для списков заметок

**Источник:** `backend_selfreview.md`, шаг 9

### Подготовка
- Применить миграцию: `docker compose exec php bin/console doctrine:migrations:migrate --no-interaction`

### Smoke (ручная проверка)
- [ ] Dashboard / `GET /api/notes` — список и infinite scroll без регрессии
- [ ] `/favorites` / `GET /api/notes?isFavorite=true` — избранные загружаются, сортировка по `updatedAt`
- [ ] `GET /api/notes/search?q=…` — поиск по-прежнему находит заметки (LIKE без full-text)
- [ ] `EXPLAIN` на типичный list-запрос — Index Scan / Bitmap Index Scan по `notes_user_active_updated_idx` (не Seq Scan на всей таблице при достаточном объёме данных)
- [ ] `EXPLAIN` на favorites — использование `notes_user_favorite_active_updated_idx`

**Ожидание:** функциональность не изменилась; list/favorites быстрее на больших выборках; `LIKE` по content остаётся без индекса (документировано в `ARCHITECTURE.md`).

### Автотесты (фаза 20+)
- Миграция up/down; EXPLAIN plan smoke в CI (опционально).

---

## BE Шаг 11 — сузить неиспользуемую поверхность API

**Источник:** `backend_selfreview.md`, шаг 11 (вариант A)

### Smoke (ручная проверка)
- [ ] `GET /api/note_links` → **404**
- [ ] `POST /api/note_links` → **404**
- [ ] `GET /api/note_versions` (глобальная коллекция) → **404**
- [ ] `GET /api/notes/{id}/backlinks` → **404**
- [ ] `GET /api/notes/{id}/versions` — **200**, список версий заметки (регрессия)
- [ ] `GET /api/notes/{id}/graph` — **200**, граф связей (регрессия)
- [ ] Сохранение заметки с wiki-ссылками — `linkStats` и граф обновляются (синхронизация `note_links` через content)

**Ожидание:** лишние эндпоинты недоступны; используемые пути версий/графа/синхронизации работают как раньше.

### Автотесты (фаза 20+)

Покрыть **все эндпоинты из ручной проверки** выше — см. [`future_autotests.md`](./future_autotests.md) «BE сузить API — removed endpoints и регрессия»:

- `GET /api/note_links` → 404
- `POST /api/note_links` → 404
- `GET /api/note_versions` (global collection) → 404
- `GET /api/notes/{id}/backlinks` → 404
- `GET /api/notes/{id}/versions` → 200
- `GET /api/notes/{id}/graph` → 200
- `PUT` заметки с wiki-ссылкой → `linkStats` и graph обновлены
- Route list: нет `note_links` CRUD, `backlinks`, global `note_versions` collection; item `GET /api/note_versions/{id}` сохранён

---

## BE Шаг 12 — паттерны и PATCH sync

**Источник:** `backend_selfreview.md`, шаг 12

### Smoke (ручная проверка)
- [ ] `PATCH /api/notes/{id}` только `isFavorite: true` — **200**; в Symfony profiler нет лишних запросов к `note_links` (sync не вызван)
- [ ] `PATCH /api/notes/{id}` только `folder` — **200**; `note_links` без изменений, если `content` тот же
- [ ] `PUT /api/notes/{id}` с изменением `content` (wiki-ссылки) — `linkStats` / граф обновлены
- [ ] `PATCH /api/auth/settings` с недопустимым `autosaveDelaySeconds` (например `7`) — **422**

**Ожидание:** sync wiki-ссылок только при изменении `content`; допустимые settings из `UserSettingOptions`.

---

## BE Шаг 13 — security headers и JWT metadata

**Источник:** `backend_selfreview.md`, шаг 13

### Smoke (ручная проверка)
- [ ] `curl -I http://localhost:8080/api/auth/me` (с JWT или без) — в ответе есть `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] `/api/docs` — title «Персональная база знаний API», не «Hello API Platform»
- [ ] В `ARCHITECTURE.md` / `.env.example` — `JWT_TOKEN_TTL`, refresh помечен как не реализован

**Ожидание:** базовые security headers на API; документация и OpenAPI metadata согласованы с MVP JWT.

---
