.PHONY: help init init-prod init-dev build build-dev up up-dev down restart status logs install migrate schema-reset seed-demo admin cache-clear test db-test frontend-test clean frontend-install frontend-build frontend-dist frontend-dev frontend-kill frontend-restart volumes-init console-php console-nginx console-cron console-postgres ensure-single-user sync-dist env

COMPOSE_DEV = docker compose -f docker-compose.yml -f docker-compose.dev.yml

help:
	@echo "Доступные команды:"
	@echo "  make env              - Сгенерировать backend/.env и frontend/.env из корневого .env"
	@echo "  make init-prod        - Prod/demo: .env, dist (CI или build), up (migrate + demo seed)"
	@echo "  make init-dev         - Dev: backend .env, overlay с node/Vite (бывший make init)"
	@echo "  make init             - Alias для make init-dev"
	@echo "  make build            - Сборка prod Docker образов (php + nginx; нужен frontend/dist)"
	@echo "  make build-dev        - Сборка с dev overlay"
	@echo "  make up               - Запуск prod (один URL :8080, SPA + API)"
	@echo "  make up-dev           - Запуск dev (API :8080, Vite :5173)"
	@echo "  make down             - Остановка контейнеров"
	@echo "  make restart          - Перезапуск контейнеров"
	@echo "  make status           - Статус контейнеров проекта"
	@echo "  make logs             - Просмотр логов (Ctrl+C для выхода)"
	@echo "  make console-php      - Интерактивная оболочка в контейнере PHP"
	@echo "  make console-nginx    - Интерактивная оболочка в контейнере Nginx"
	@echo "  make console-cron     - Интерактивная оболочка в контейнере Cron"
	@echo "  make console-postgres - Интерактивная оболочка в контейнере PostgreSQL"
	@echo "  make install          - Установка зависимостей Composer (backend)"
	@echo "  make migrate          - Применение миграций базы данных"
	@echo "  make db-test          - Создание test БД (otus_ai_db_test) для PHPUnit"
	@echo "  make schema-reset     - Очистка схемы БД и повторное применение миграций"
	@echo "  make seed-demo        - Загрузка demo-данных (3 вселенные, --force)"
	@echo "  make admin            - Создание администратора из .env"
	@echo "  make cache-clear      - Очистка кэша Symfony"
	@echo "  make test             - PHPUnit (backend)"
	@echo "  make frontend-test    - Vitest (frontend)"
	@echo "  make frontend-install - Установка зависимостей npm (frontend)"
	@echo "  make frontend-build   - Сборка frontend/dist локально (npm ci + vite build)"
	@echo "  make frontend-dist    - frontend/dist: sync-dist или fallback на frontend-build"
	@echo "  make sync-dist        - Подтянуть frontend/dist с ветки dist (CI)"
	@echo "  make frontend-dev     - Запуск Vite dev server"
	@echo "  make frontend-kill    - Остановка всех Node.js/Vite процессов"
	@echo "  make frontend-restart - Перезапуск Vite dev server"
	@echo "  make clean            - Удаление всех контейнеров, образов и volumes"

volumes-init:
	@mkdir -p volumes/postgres volumes/node_modules

init-dev: volumes-init
	@echo "Инициализация dev-окружения (Vite + mount backend)..."
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@$(MAKE) env
	@echo "ВАЖНО: Отредактируйте корневой .env (секреты, пароли)"
	@echo "Нажмите Enter для продолжения..."
	@read dummy
	@$(MAKE) build-dev
	@$(MAKE) up-dev
	@echo "Ожидание PostgreSQL..."
	@sleep 5
	@$(MAKE) install
	@$(MAKE) migrate
	@$(MAKE) admin
	@echo ""
	@echo "✅ Dev окружение готово"
	@echo "🌐 API: http://localhost:8080/api"
	@echo "💻 UI:  http://localhost:5173"

init-prod: volumes-init
	@echo "Инициализация prod/demo (static SPA, без node)..."
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@$(MAKE) env
	@sed -i 's|^VITE_API_URL=.*|VITE_API_URL=/api|' frontend/.env
	@echo "Проверьте корневой .env (APP_AUTH_ENABLED, секреты, DB)"
	@echo "Нажмите Enter для продолжения..."
	@read dummy
	@$(MAKE) frontend-dist
	@$(MAKE) build
	@$(MAKE) up
	@echo "Ожидание bootstrap (migrate + demo seed / ensure-single-user)..."
	@sleep 8
	@echo ""
	@echo "✅ Prod/demo окружение: http://localhost:$${APP_PORT:-8080}/"
	@echo "   Demo: hogwarts@demo.local / westeros@demo.local / witcher@demo.local — пароль demo1234"

init: init-dev

env:
	@chmod +x scripts/generate-env.sh
	@./scripts/generate-env.sh

build: env
	docker compose build

build-dev: env
	$(COMPOSE_DEV) build

rebuild:
	docker compose build --no-cache

up: env volumes-init
	docker compose up -d

up-dev: env volumes-init
	$(COMPOSE_DEV) up -d

down:
	docker compose down

restart:
	@$(MAKE) down
	@$(MAKE) up

status:
	@docker compose ps

logs:
	docker compose logs -f

console-php:
	docker exec -it otus_php sh

console-nginx:
	docker exec -it otus_nginx sh

console-cron:
	docker exec -it otus_cron sh

console-postgres:
	docker exec -it otus_postgres sh

install:
	docker exec otus_php composer install --no-interaction

migrate:
	docker exec otus_php bin/console doctrine:migrations:migrate --no-interaction

db-test:
	docker exec otus_php bin/console doctrine:database:create --env=test --if-not-exists

schema-reset:
	@echo "⚠️  Очистка схемы БД и повторное применение миграций..."
	docker exec otus_php bin/console app:reset-schema
	@echo "✅ Схема пересоздана"

seed-demo:
	@echo "Загрузка demo-данных..."
	@docker exec otus_php bin/console app:seed-demo-data --force

admin:
	@echo "Создание администратора..."
	@docker exec otus_php bin/console app:create-admin

cache-clear:
	docker exec otus_php bin/console cache:clear

test: db-test
	docker exec otus_php bin/phpunit

frontend-test:
	@$(COMPOSE_DEV) up -d node 2>/dev/null || true
	docker exec otus_node npm test

frontend-install:
	@$(COMPOSE_DEV) up -d node 2>/dev/null || true
	docker exec otus_node npm install

frontend-build: env
	@echo "Сборка frontend/dist..."
	@docker run --rm \
		-v "$(CURDIR)/frontend:/app" \
		-w /app \
		--env-file "$(CURDIR)/frontend/.env" \
		node:22-alpine \
		sh -c "npm ci && npm run build"

frontend-dist:
	@echo "Получение frontend/dist..."
	@if git fetch origin dist 2>/dev/null && git rev-parse --verify origin/dist >/dev/null 2>&1; then \
		rm -rf frontend/dist .dist-source-sha 2>/dev/null \
			|| docker run --rm -v "$(CURDIR):/repo" -w /repo alpine sh -c "rm -rf frontend/dist .dist-source-sha"; \
		git restore --source=origin/dist --worktree -- frontend/dist .dist-source-sha; \
		if [ -f frontend/dist/index.html ]; then \
			echo "✅ dist с origin/dist (source: $$(cat .dist-source-sha 2>/dev/null || echo unknown))"; \
		else \
			echo "⚠️  sync-dist не удался — локальная сборка (frontend-build)..."; \
			$(MAKE) frontend-build; \
		fi; \
	elif [ -f frontend/dist/index.html ]; then \
		echo "⚠️  origin/dist недоступна — используется существующий frontend/dist"; \
	else \
		echo "⚠️  origin/dist недоступна — локальная сборка (frontend-build)..."; \
		$(MAKE) frontend-build; \
	fi

sync-dist:
	@echo "Подтягивание frontend/dist с origin/dist..."
	@rm -rf frontend/dist .dist-source-sha 2>/dev/null \
		|| docker run --rm -v "$(CURDIR):/repo" -w /repo alpine sh -c "rm -rf frontend/dist .dist-source-sha"
	git fetch origin dist
	git restore --source=origin/dist --worktree -- frontend/dist .dist-source-sha
	@echo "✅ dist обновлён (source: $$(cat .dist-source-sha 2>/dev/null || echo unknown))"

frontend-dev:
	@echo "Запуск Vite dev server..."
	@$(COMPOSE_DEV) up -d node 2>/dev/null || true
	docker exec -it otus_node sh -c "cd /app && npm run dev"

frontend-kill:
	@echo "Остановка Node.js/Vite процессов..."
	@docker exec otus_node sh -c "pkill -9 node || true" 2>/dev/null || echo "⚠️  Контейнер node не запущен"
	@echo "✅ Завершено"

frontend-restart: frontend-kill
	@echo "Перезапуск через 2 секунды..."
	@sleep 2
	@$(MAKE) frontend-dev

clean:
	@echo "⚠️  ВНИМАНИЕ: Эта команда удалит все контейнеры, образы и данные в volumes/"
	@echo "Нажмите Ctrl+C для отмены или Enter для продолжения..."
	@read dummy
	docker compose down --rmi all
	rm -rf volumes/postgres volumes/node_modules
	@$(MAKE) volumes-init
	@echo "✅ Очистка завершена"
