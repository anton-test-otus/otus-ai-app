# Self-review: фронтенд (фаза 18)

**Дата:** 2026-06-13  
**Область:** `frontend/src`  
**Статус:** ревью выполнено; правки — отдельными коммитами по шагам ниже.

Исправления по каждому шагу можно коммитить отдельно. Внутри шага пункты с `- [ ]` — чеклист задач.

---

## Сводка

| Категория | Critical | Medium | Low |
|-----------|:--------:|:------:|:---:|
| Безопасность (XSS / sanitization) | 1 | 1 | 1 |
| Запахи и структура | 0 | 4 | 3 |
| Дублирование | 0 | 6 | 2 |
| Мёртвый код / видимость API | 0 | 5 | 4 |
| Согласованность паттернов | 0 | 5 | 3 |
| Неиспользуемые зависимости | 0 | 2 | 0 |

---

## Шаг 1. Critical: XSS в подсветке поиска

**Приоритет:** critical  
**Коммит:** `fix(frontend): escape HTML in SearchBar highlight`

**Проблема:** `SearchBar.vue` рендерит `note.title` и `note.contentPreview` через `v-html="highlightMatch(...)"`. `highlightMatch` оборачивает совпадения в `<mark>`, но **не экранирует HTML** в исходном тексте. Заголовок вида `<img src=x onerror=alert(1)>` выполнится как HTML даже без совпадения с запросом. `escapeRegex` защищает только regex, не DOM.

**Файлы:** `frontend/src/components/common/SearchBar.vue` (строки 58, 95, 100, 340–345)

- [x] Добавить `escapeHtml()` в `utils/` (или рядом с `highlightMatch`)
- [x] В `highlightMatch`: сначала `escapeHtml(text)`, затем обёртка совпадений в `<mark>`
- [x] Либо отказаться от `v-html`: разбить текст на сегменты и рендерить `<mark>` в шаблоне
- [x] Smoke: заметка с `<script>` / `<img onerror=...>` в title — в результатах поиска отображается как текст

---

## Шаг 2. Безопасность: raw HTML в markdown preview

**Приоритет:** medium  
**Коммит:** `fix(frontend): sanitize markdown HTML in preview`

**Проблема:** Milkdown (`commonmark` + `gfm`) без слоя санитизации HTML. CommonMark допускает raw HTML-блоки; при прохождении через pipeline возможен XSS в preview и print.

**Уже есть:** wiki-ссылки через `textContent` (`wikiLinkNode.ts`); внешние URL — whitelist в `utils/url.ts`. `sanitizeNoteText` убирает только control chars — **не** XSS-защита.

**Файлы:** `MarkdownPreview.vue`, `MarkdownEditor.vue`, `NotePrintView.vue`, `utils/sanitizeText.ts`

- [x] Выбрать подход: remark-плагин (отключить HTML-узлы) или DOMPurify перед рендером
- [x] Применить единообразно в preview, editor view и print
- [x] Переименовать или задокументировать `sanitizeNoteText` / `sanitizeNoteContent` как **нормализацию**, не XSS defense
- [x] Smoke: markdown с `<script>`, `<img onerror=...>` — не выполняется в preview

---

## Шаг 3. Мёртвые npm-зависимости

**Приоритет:** medium  
**Коммит:** `chore(frontend): remove unused dependencies`

**Проблема:** в `package.json` не импортируются:

| Пакет | Причина |
|-------|---------|
| `marked` | Milkdown рендерит markdown |
| `vue-draggable-plus` | DnD папок снят в фазе 5; заметки — native HTML5 DnD |
| `@milkdown/plugin-tooltip` | не импортируется |
| `@milkdown/theme-nord` | кастомные Tailwind-стили |
| `@milkdown/vue` | используется `Editor.make()` напрямую |

**Transitive без прямой зависимости:** `unist-util-visit`, `@types/mdast` — импорт в `wikiLinkNode.ts`

- [x] `docker compose exec node npm uninstall marked vue-draggable-plus @milkdown/plugin-tooltip @milkdown/theme-nord @milkdown/vue`
- [x] `docker compose exec node npm install unist-util-visit`
- [x] `docker compose exec node npm install -D @types/mdast` (если нужен для tsc)
- [x] `docker compose exec node npm run build` — без ошибок

---

## Шаг 4. Мёртвый код: store methods и exports

**Приоритет:** medium  
**Коммит:** `refactor(frontend): remove dead store and API exports`

| Символ | Файл | Статус |
|--------|------|--------|
| `searchNotes` | `stores/notes.ts` | export, нет вызовов |
| `getTagNotes` | `stores/tags.ts` | export, нет вызовов |
| `wikiLinksApi.getBacklinks` | `api/wikilinks.ts` | UI удалён в фазе 14.3 |
| `wikiLinkPreviewPlugins` / `wikiLinkPlugins` | `wikiLinkNode.ts` | export, нет импортов |
| `flatFolders` | `stores/folders.ts` | export, нет импортов |

- [x] Удалить `searchNotes` из `notes` store (или подключить, если нужен)
- [x] Удалить `getTagNotes` из `tags` store
- [x] Удалить `getBacklinks` из `wikilinks` API (endpoint на бэкенде можно оставить)
- [x] Удалить неиспользуемые exports из `wikiLinkNode.ts`
- [x] Удалить `flatFolders` из `folders` store
- [x] Заменить `folderTree` на `folders` в `AppLayout.vue`; убрать alias из store

---

## Шаг 5. JWT refresh: реализовать или убрать

**Приоритет:** medium  
**Статус:** ⏸ отложен — сначала решение на бэкенде ([`backend_selfreview.md` — шаг 15](./backend_selfreview.md#шаг-15-jwt-refresh-блокирует-фронтенд-шаг-5))  
**Коммит:** `fix(frontend): jwt refresh flow` **или** `refactor(frontend): remove unused refresh token storage`

**Проблема:** `refreshToken` сохраняется в localStorage при login/register; `authApi.refresh` существует, но на 401 `client.ts` сразу очищает токены и редиректит на `/login` без попытки refresh. Endpoint `/api/auth/refresh` описан в `ARCHITECTURE.md`, но **на бэкенде не реализован**; login/register отдают только `token` + `user`.

**Файлы:** `stores/auth.ts`, `api/auth.ts`, `api/client.ts`

- [ ] **Вариант A:** в interceptor на 401 — вызов `authApi.refresh`, обновление token, retry запроса *(требует шаг 15 на бэкенде)*
- [ ] **Вариант B:** убрать хранение `refreshToken` и метод `refresh`, если refresh не нужен в MVP
- [ ] Зафиксировать выбор в `ARCHITECTURE.md`

---

## Шаг 6. Единый парсер Hydra collection

**Приоритет:** medium  
**Коммит:** `refactor(frontend): extract parseHydraCollection helper`

**Проблема:** паттерн `(response['hydra:member'] || response['member'] || [])` повторяется в:

- `api/notes.ts` (3 места)
- `api/trash.ts`
- `api/tags.ts` (2 места)
- `api/folders.ts`
- `composables/useNoteVersions.ts`

- [x] Добавить `parseHydraCollection<T>(response): { data: T[], total: number }` в `utils/hydra.ts` или `api/client.ts`
- [x] Заменить все вхождения
- [x] При необходимости — `parseHydraTotal` для `hydra:totalItems`

---

## Шаг 7. Единый API поиска заметок

**Приоритет:** medium  
**Коммит:** `refactor(frontend): consolidate note search API`

**Проблема:** разрозненные вызовы без явной семантики; модалка wiki-ссылок должна искать **только по title**, SearchBar — полнотекст.

| Метод | Endpoint | Семантика | UI |
|-------|----------|-----------|-----|
| `searchApi.search` | `/notes/search?q=...` | title + content | `SearchBar.vue` |
| `searchApi.searchByTitle` | `/notes?title=...` | title only | `LinkNoteModal.vue` |
| `notesApi.filter` | `/notes/search` без `q` | folder/tags | Dashboard |

- [x] Добавить `searchApi.searchByTitle` → `GET /notes?title=...`
- [x] `LinkNoteModal.vue` — `searchByTitle`, не полнотекстовый `search`
- [x] Удалён мёртвый `notesApi.search` (если был)
- [x] Smoke: модалка не находит по слову только из content; SearchBar — находит

---

## Шаг 8. Дублирование paginated fetch в notes store

**Приоритет:** medium  
**Коммит:** `refactor(frontend): dedupe fetchNotes and fetchFavorites`

**Проблема:** `fetchNotes` и `fetchFavorites` (~90% идентичны): in-flight promise, append, pagination meta, error handling.

**Файл:** `frontend/src/stores/notes.ts`

- [x] Вынести внутренний `fetchPaginatedList(config)` с параметрами: fetchFn, append, criteriaKey, target refs
- [x] `fetchNotes` / `fetchFavorites` — тонкие обёртки
- [x] Проверить infinite scroll на dashboard и `/favorites`

---

## Шаг 9. Общие утилиты: фильтры, дерево папок, даты

**Приоритет:** medium / low  
**Коммит:** `refactor(frontend): shared filter and date utils`

**Проблема:**

- `buildListCriteriaKey` / `buildCriteriaKey` — одинаковый `JSON.stringify({ folderId, tags: sorted })` в `notes.ts` и `tags.ts`
- Flatten дерева: `useFolderDropdownOptions`, рекурсивный `getFolderById` в `folders` store
- `formatDate` + `pluralizeNotes` — копии в `DashboardView`, `FavoritesView`, `TrashView`; в utils уже есть `formatRelativeDate`, `pluralizeRu`
- `auth.ts`: одинаковый блок сохранения token/localStorage в `login` и `register`

- [ ] `utils/filters.ts` — `buildFilterCriteriaKey(folderId?, tagIds?)`
- [ ] `utils/folders.ts` — `flattenFolderTree`, `findFolderInTree` (использовать в dropdown и store)
- [ ] `utils/date.ts` — `formatCardDate()`; `utils/pluralize.ts` — `pluralizeNotes()`; заменить копии в views
- [ ] `auth.ts` — private `applyAuthResponse(response)`

---

## Шаг 10. Разделение loading/error в notes store

**Приоритет:** medium  
**Коммит:** `refactor(frontend): split notes store list and detail state`

**Проблема:** god store `notes.ts` (476 строк). Общие `isLoading` / `error` для list fetch, detail fetch, create, delete, search — ошибка загрузки заметки может «утекать» на dashboard.

- [ ] Ввести `isLoadingList` / `isLoadingDetail` (или аналог)
- [ ] Ввести `listError` / `detailError`
- [ ] Мутации (`toggleFavorite`, `moveNoteToFolder`, `updateNote`) — только toast, без записи в list `error`
- [ ] Обновить views, читающие `notesStore.error` / `isLoading`

---

## Шаг 11. Рефакторинг NoteView

**Приоритет:** medium (можно отложить)  
**Коммит:** `refactor(frontend): extract NoteView composables`

**Проблема:** `NoteView.vue` — 874 строки: routing, черновик, autosave, метаданные, export, restore версий, shortcuts, favorite, delete.

- [ ] `useNoteDraft` — черновик, autosave, snapshot, persist mutex
- [ ] `useNoteNavigation` — goBack, leaveNote, route guards
- [ ] `useNoteToolbarActions` — export, delete, favorite, version/graph dialogs
- [ ] `NoteView.vue` — оркестрация + template

---

## Шаг 12. Рефакторинг редактора и FolderTreeItem

**Приоритет:** medium (можно отложить)  
**Коммит:** `refactor(frontend): split MarkdownEditor and FolderTreeItem`

**Проблема:**

- `MarkdownEditor.vue` — 614 строк (Milkdown, toolbar, link/wiki dialogs, shortcuts)
- `FolderTreeItem.vue` — 352 строки (CRUD, delete confirm, DnD, expand)

- [ ] `useMilkdownEditor` — инициализация editor, sync content
- [ ] `useLinkDialog` / `useWikiLinkDialog` — модалки ссылок
- [ ] Общая фабрика Milkdown plugins для editor и preview (сейчас дублируется)
- [ ] CRUD папок — composable `useFolderCrud` или дочерние dialog-комponents

---

## Шаг 13. Согласованность паттернов

**Приоритет:** medium / low  
**Коммит:** `refactor(frontend): unify loading, errors, imports`

**Проблема:** расхождения с `ARCHITECTURE.md` (фаза 12).

| Область | Сейчас | Ожидается |
|---------|--------|-----------|
| Loading flag | `isLoading` vs `loading` | едино `isLoading` |
| Ошибки auth | `catch (err: any)` + `err.message` | `getApiErrorMessage` |
| trash store | silent catch, нет `error` | log или error state |
| SearchBar / LinkNoteModal | `console.error` | `useAppToast` |
| MarkdownPreview | прямой `useToast()` | `useAppToast()` |
| TrashView | `trashStore.count = meta.total` | `fetchCount()` / `setCount()` |
| Import paths | `../` vs `@/` | единый `@/` |
| noteDragDrop | нет `reset()` в logout | добавить в `resetUserStores` |

- [ ] Переименовать `loading` → `isLoading` в folders, tags, `useNoteVersions`
- [ ] Заменить ad-hoc error handling на `getApiErrorMessage` + toast/ErrorState
- [ ] `trashStore.setCount(n)` или всегда `fetchCount()` после мутаций корзины
- [ ] Унифицировать imports на `@/`
- [ ] `noteDragDrop.reset()` в `resetUserStores.ts`

---

## Шаг 14. Мелкие улучшения (low, optional)

**Приоритет:** low  
**Коммит:** по желанию, можно включить в шаг 13

- [ ] `NoteListCriteria` в `api/notes.ts` — unexport или перенести в `types`
- [ ] `HttpError` в `client.ts` — оставить export для тестов или перенести
- [ ] `useConfirmAction()` — общий composable для confirm-delete (Dashboard, Favorites, NoteView, FolderTreeItem, TagsView), если оправдано по объёму

---

## Положительные находки (не требуют правок)

- Порядок `loading` → `error` → `empty` → контент в основных views
- `useAppToast` + `getApiErrorMessage` для мутаций
- Wiki-ссылки: `textContent`, без `v-html` в редакторе/preview
- In-flight dedup в stores notes/folders/tags
- `resetUserStores` + очистка wiki title cache при logout
- Нормализация текста на пути сохранения (`api/notes.ts`, `NoteView`)
- Whitelist протоколов для внешних ссылок (`utils/url.ts`)

---

## Порядок коммитов (рекомендуемый)

1. Шаг 1 — XSS SearchBar (**обязательно первым**)
2. Шаг 2 — markdown HTML
3. Шаг 3 — deps
4. Шаг 4 — dead exports
5. ~~Шаг 5 — JWT refresh~~ (отложен → backend шаг 15)
6. Шаг 6 — Hydra parser
7. Шаг 7 — search API
8. Шаг 8 — paginated fetch
9. Шаг 9 — shared utils
10. Шаг 10 — notes store state
11. Шаг 13 — паттерны (можно параллельно с 9–10)
12. Шаг 11–12 — крупный рефакторинг (follow-up, если не успеваем до фазы 19)
13. Шаг 14 — optional

После выполнения шага — отметить `- [x]` в этом файле и кратко зафиксировать в `REPORT.md`.

---

## Доработки после ревью (backlog)

Задачи, выявленные при smoke или ревью, но **вне** шагов 1–14. Не блокируют прохождение selfreview; после основных шагов или в фазе 20.

### Поиск заметок: регистронезависимый

**Источник:** smoke шага 6 (`notesApi.search` в `LinkNoteModal`); аналогично затрагивает `SearchBar` → `GET /notes/search`.

**Проблема:** поиск по title (и полнотекстовый в `NoteRepository::search`) **регистрозависимый**. Заметка «Hello World» не находится по запросу `hello`. На бэкенде: `LIKE :query` без `LOWER`/`ILIKE` в `NoteRepository::search`; `SearchFilter` partial по `title` на `GET /notes` — тоже case-sensitive в PostgreSQL.

**Ожидаемое поведение:** поиск без учёта регистра (как уже сделано в `findByTitleCaseInsensitive` для wiki-ссылок).

**Где править:**
- **Бэкенд (основное):** `NoteRepository::search` — `LOWER(n.title) LIKE LOWER(:query)` (и content, если нужна та же семантика); для `GET /notes?title=` — кастомный filter или репозиторий вместо `SearchFilter partial`
- **Фронт:** после фикса бэка — smoke `SearchBar` и `LinkNoteModal`; подсветка в `highlightMatch` может остаться case-sensitive (отдельно, low)

**Smoke после фикса:**
- [ ] Заметка с title `Hello World` — запрос `hello` / `HELLO` находит её в SearchBar и в модалке wiki-ссылки
- [ ] Полнотекстовый поиск (`q` в content) — тот же регистронезависимый критерий

**Связь:** [`backend_selfreview.md` — доработки после ревью](./backend_selfreview.md#доработки-после-ревью-backlog)
