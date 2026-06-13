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
- [ ] Wiki-ссылка → модалка выбора заметки (`notesApi.search`) — результаты по title

**Ожидание:** все списки загружаются без ошибок; пагинация и пустые состояния как до рефакторинга.

**Находка (backlog):** поиск в модалке wiki-ссылок регистрозависимый — см. «Доработки после ревью» в `frontend_selfreview.md` / `backend_selfreview.md`.

### Автотесты (позже)
- Unit: `parseHydraCollection` — `hydra:member`, `member`, голый массив, `totalItems = 0`.
