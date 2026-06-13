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
| 10 | `GET /api/note_links/{linkB.id}` + token A (если есть fixture) | **404** |

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
