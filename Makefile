.PHONY: help init init-prod init-dev build build-dev up up-dev down restart status logs install migrate schema-reset seed-demo seed-demo-if-missing admin cache-clear test db-test frontend-test clean frontend-install frontend-build frontend-dist frontend-dev frontend-kill frontend-restart volumes-init jwt-keys console-php console-nginx console-cron console-postgres ensure-single-user sync-dist env

# DOCKER_ENV из корневого .env (dev | demo) или override: make up DOCKER_ENV=demo
DOCKER_ENV ?= $(shell grep -E '^DOCKER_ENV=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r' | sed 's/^[[:space:]]*//;s/[[:space:]]*$$//')
DOCKER_ENV := $(if $(filter demo dev,$(strip $(DOCKER_ENV))),$(strip $(DOCKER_ENV)),demo)

ifeq ($(DOCKER_ENV),dev)
COMPOSE = docker compose -f docker-compose.yml -f docker-compose.dev.yml
else
COMPOSE = docker compose
endif

# Dev overlay для frontend-* (node), даже если основной стек в demo
COMPOSE_DEV = docker compose -f docker-compose.yml -f docker-compose.dev.yml

# APP_NAME из корневого .env (default: otus_ai)
APP_NAME := $(shell grep -E '^APP_NAME=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r' | sed 's/^[[:space:]]*//;s/[[:space:]]*$$//')
APP_NAME := $(if $(strip $(APP_NAME)),$(APP_NAME),otus_ai)
VOLUME_DIR = volumes/$(APP_NAME)

help:
	@echo "DOCKER_ENV=$(DOCKER_ENV) (dev | demo — в корневом .env или make … DOCKER_ENV=demo)"
	@echo ""
	@echo "Доступные команды:"
	@echo "  make env              - Сгенерировать backend/.env и frontend/.env из корневого .env"
	@echo "  make jwt-keys         - JWT keys на хосте (fallback; в Docker — из образа при старте php)"
	@echo "  make init             - Первичная настройка (режим из DOCKER_ENV: dev или demo)"
	@echo "  make build            - Сборка Docker-образов (compose по DOCKER_ENV)"
	@echo "  make up               - Запуск контейнеров (dev: API :8080 + Vite :5173; demo: :8080)"
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
	@echo "  make seed-demo-if-missing - Demo seed, если пользователей ещё нет (--if-missing)"
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
	@mkdir -p $(VOLUME_DIR)/node_modules 2>/dev/null || true
	@touch $(VOLUME_DIR)/node_modules/.gitkeep 2>/dev/null || true
	@docker run --rm -v "$(CURDIR)/$(VOLUME_DIR):/vol" alpine sh -c '\
		mkdir -p /vol/postgres/data /vol/node_modules; \
		test -f /vol/postgres/.gitkeep || touch /vol/postgres/.gitkeep; \
		test -f /vol/node_modules/.gitkeep || touch /vol/node_modules/.gitkeep; \
		chmod 755 /vol/postgres'

init: volumes-init
	@echo "Инициализация окружения DOCKER_ENV=$(DOCKER_ENV)..."
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@$(MAKE) env
	@if [ "$(DOCKER_ENV)" = "demo" ]; then \
		sed -i 's|^VITE_API_URL=.*|VITE_API_URL=/api|' frontend/.env; \
		echo "Проверьте корневой .env (APP_AUTH_ENABLED, секреты, DB)"; \
	else \
		echo "ВАЖНО: Отредактируйте корневой .env (секреты, пароли)"; \
	fi
	@echo "Нажмите Enter для продолжения..."
	@read dummy
	@if [ "$(DOCKER_ENV)" = "demo" ]; then $(MAKE) frontend-dist; fi
	@$(MAKE) build
	@$(MAKE) up
	@if [ "$(DOCKER_ENV)" = "demo" ]; then \
		echo "Ожидание bootstrap (migrate)..."; \
		sleep 8; \
		if grep -qE '^APP_AUTH_ENABLED=(true|1)' .env 2>/dev/null; then $(MAKE) seed-demo-if-missing; fi; \
		echo ""; \
		echo "✅ Demo окружение: http://localhost:$${APP_PORT:-8080}/"; \
		echo "   Demo: hogwarts@demo.local / westeros@demo.local / witcher@demo.local — пароль demo1234"; \
	else \
		echo "Ожидание PostgreSQL..."; \
		sleep 5; \
		$(MAKE) install; \
		$(MAKE) migrate; \
		$(MAKE) admin; \
		if grep -qE '^APP_AUTH_ENABLED=(true|1)' .env 2>/dev/null; then $(MAKE) seed-demo-if-missing; fi; \
		echo ""; \
		echo "✅ Dev окружение готово"; \
		echo "🌐 API: http://localhost:8080/api"; \
		echo "💻 UI:  http://localhost:5173"; \
	fi

# Обратная совместимость
init-dev:
	@$(MAKE) init DOCKER_ENV=dev

init-prod:
	@$(MAKE) init DOCKER_ENV=demo

env:
	@chmod +x scripts/generate-env.sh
	@./scripts/generate-env.sh
	@$(MAKE) jwt-keys

jwt-keys:
	@chmod +x scripts/generate-jwt-keys.sh
	@./scripts/generate-jwt-keys.sh

build: env
	$(COMPOSE) build

build-dev:
	@$(MAKE) build DOCKER_ENV=dev

rebuild:
	$(COMPOSE) build --no-cache

up: env volumes-init
	$(COMPOSE) up -d

up-dev:
	@$(MAKE) up DOCKER_ENV=dev

down:
	$(COMPOSE) down

restart:
	@$(MAKE) down
	@$(MAKE) up

status:
	@$(COMPOSE) ps

logs:
	$(COMPOSE) logs -f

console-php:
	$(COMPOSE) exec php sh

console-nginx:
	$(COMPOSE) exec nginx sh

console-cron:
	$(COMPOSE) exec cron sh

console-postgres:
	$(COMPOSE) exec postgres sh

install:
	$(COMPOSE) exec php composer install --no-interaction

migrate:
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate --no-interaction

db-test:
	$(COMPOSE) exec php bin/console doctrine:database:create --env=test --if-not-exists

schema-reset:
	@echo "⚠️  Очистка схемы БД и повторное применение миграций..."
	$(COMPOSE) exec php bin/console app:reset-schema
	@echo "✅ Схема пересоздана"

seed-demo:
	@echo "Загрузка demo-данных..."
	@$(COMPOSE) exec php bin/console app:seed-demo-data --force

seed-demo-if-missing:
	@echo "Загрузка demo-данных (если ещё нет)..."
	@$(COMPOSE) exec php bin/console app:seed-demo-data --if-missing

admin:
	@echo "Создание администратора..."
	@$(COMPOSE) exec php bin/console app:create-admin

cache-clear:
	$(COMPOSE) exec php bin/console cache:clear

test: db-test
	$(COMPOSE) exec php bin/phpunit

frontend-test:
	@$(COMPOSE_DEV) up -d node 2>/dev/null || true
	$(COMPOSE_DEV) exec node npm test

frontend-install:
	@$(COMPOSE_DEV) up -d node 2>/dev/null || true
	$(COMPOSE_DEV) exec node npm install

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
	$(COMPOSE_DEV) exec node sh -c "cd /app && npm run dev"

frontend-kill:
	@echo "Остановка Node.js/Vite процессов..."
	@$(COMPOSE_DEV) exec node sh -c "pkill -9 node || true" 2>/dev/null || echo "⚠️  Контейнер node не запущен"
	@echo "✅ Завершено"

frontend-restart: frontend-kill
	@echo "Перезапуск через 2 секунды..."
	@sleep 2
	@$(MAKE) frontend-dev

clean:
	@echo "⚠️  ВНИМАНИЕ: Эта команда удалит все контейнеры, образы и данные в volumes/"
	@echo "Нажмите Ctrl+C для отмены или Enter для продолжения..."
	@read dummy
	$(COMPOSE) down --rmi all
	@mkdir -p $(VOLUME_DIR)/postgres/data $(VOLUME_DIR)/node_modules 2>/dev/null || true
	@docker run --rm -v "$(CURDIR)/$(VOLUME_DIR):/vol" alpine sh -c '\
		mkdir -p /vol/node_modules; \
		find /vol/node_modules -mindepth 1 ! -name .gitkeep -exec rm -rf {} +; \
		mkdir -p /vol/postgres; \
		find /vol/postgres -mindepth 1 -maxdepth 1 ! -name .gitkeep ! -name data -exec rm -rf {} +; \
		rm -rf /vol/postgres/data; \
		mkdir -p /vol/postgres/data; \
		test -f /vol/postgres/.gitkeep || touch /vol/postgres/.gitkeep; \
		chmod 755 /vol/postgres'
	@$(MAKE) volumes-init
	@echo "✅ Очистка завершена"
