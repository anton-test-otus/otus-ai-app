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
