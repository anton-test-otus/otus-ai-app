.PHONY: help init build up down restart status logs install migrate schema-reset seed-demo admin cache-clear test clean frontend-install frontend-build frontend-dev frontend-kill frontend-restart volumes-init console-php console-nginx console-cron console-postgres

help:
	@echo "Доступные команды:"
	@echo "  make init             - Первоначальная настройка проекта (копирование .env, сборка, запуск)"
	@echo "  make build            - Сборка Docker образов"
	@echo "  make rebuild          - Сборка Docker образов без кэша"
	@echo "  make up               - Запуск контейнеров"
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
	@echo "  make schema-reset     - Очистка схемы БД и повторное применение миграций"
	@echo "  make seed-demo        - Загрузка demo-данных (3 вселенные, --force)"
	@echo "  make admin            - Создание администратора из .env"
	@echo "  make cache-clear      - Очистка кэша Symfony"
	@echo "  make test             - Запуск тестов"
	@echo "  make frontend-install - Установка зависимостей npm (frontend)"
	@echo "  make frontend-build   - Сборка production фронтенда"
	@echo "  make frontend-dev     - Запуск Vite dev server"
	@echo "  make frontend-kill    - Остановка всех Node.js/Vite процессов"
	@echo "  make frontend-restart - Перезапуск Vite dev server"
	@echo "  make clean            - Удаление всех контейнеров, образов и volumes"

volumes-init:
	@mkdir -p volumes/postgres volumes/node_modules

init: volumes-init
	@echo "Инициализация проекта..."
	@if [ ! -f backend/.env ]; then \
		echo "Копирование .env.example в .env..."; \
		cp backend/.env.example backend/.env; \
		echo "ВАЖНО: Отредактируйте backend/.env и установите безопасные значения!"; \
		echo "Нажмите Enter для продолжения..."; \
		read dummy; \
	fi
	@echo "Сборка Docker образов..."
	@$(MAKE) build
	@echo "Запуск контейнеров..."
	@$(MAKE) up
	@echo "Ожидание запуска PostgreSQL..."
	@sleep 5
	@echo "Установка зависимостей..."
	@$(MAKE) install
	@echo "Применение миграций..."
	@$(MAKE) migrate
	@echo "Создание администратора..."
	@$(MAKE) admin
	@echo ""
	@echo "✅ Проект успешно инициализирован!"
	@echo "🌐 API доступен на http://localhost:8080/api"
	@echo "📚 Swagger UI доступен на http://localhost:8080/api/docs"
	@echo "💻 Frontend dev server на http://localhost:5173"

build:
	docker compose build

rebuild:
	docker compose build --no-cache

up: volumes-init
	docker compose up -d

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

test:
	docker exec otus_php bin/phpunit

frontend-install:
	@docker compose start node 2>/dev/null || true
	docker exec otus_node npm install

frontend-build:
	@docker compose start node 2>/dev/null || true
	docker exec otus_node npm run build

frontend-dev:
	@echo "Запуск Vite dev server..."
	@docker compose start node 2>/dev/null || true
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
