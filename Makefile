.PHONY: help init build up down restart logs install migrate admin cache-clear test clean

help:
	@echo "Доступные команды:"
	@echo "  make init          - Первоначальная настройка проекта (копирование .env, сборка, запуск)"
	@echo "  make build         - Сборка Docker образов"
	@echo "  make up            - Запуск контейнеров"
	@echo "  make down          - Остановка контейнеров"
	@echo "  make restart       - Перезапуск контейнеров"
	@echo "  make logs          - Просмотр логов (Ctrl+C для выхода)"
	@echo "  make install       - Установка зависимостей Composer"
	@echo "  make migrate       - Применение миграций базы данных"
	@echo "  make admin         - Создание администратора из .env"
	@echo "  make cache-clear   - Очистка кэша Symfony"
	@echo "  make test          - Запуск тестов"
	@echo "  make clean         - Удаление всех контейнеров, образов и volumes"

init:
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

build:
	docker compose build --no-cache

up:
	docker compose up -d

down:
	docker compose down

restart:
	@$(MAKE) down
	@$(MAKE) up

logs:
	docker compose logs -f

install:
	docker exec otus_php composer install --no-interaction

migrate:
	docker exec otus_php bin/console doctrine:migrations:migrate --no-interaction

admin:
	@echo "Создание администратора..."
	@docker exec otus_php bin/console app:create-admin

cache-clear:
	docker exec otus_php bin/console cache:clear

test:
	docker exec otus_php bin/phpunit

clean:
	@echo "⚠️  ВНИМАНИЕ: Эта команда удалит все контейнеры, образы и volumes!"
	@echo "Нажмите Ctrl+C для отмены или Enter для продолжения..."
	@read dummy
	docker compose down -v --rmi all
	@echo "✅ Очистка завершена"
