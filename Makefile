# =============================================================================
# SLMS — Developer Makefile
# Usage: make <target>
# =============================================================================

.PHONY: help up down build restart logs shell-backend shell-frontend \
        migrate seed fresh test lint artisan npm

# Default target
help:
	@echo ""
	@echo "SLMS Development Commands"
	@echo "========================="
	@echo ""
	@echo "  make up              Start all services"
	@echo "  make down            Stop all services"
	@echo "  make build           Build/rebuild all images"
	@echo "  make restart         Rebuild and restart"
	@echo "  make logs            Tail all service logs"
	@echo "  make logs-backend    Tail backend logs only"
	@echo ""
	@echo "  make shell-backend   Open shell in backend container"
	@echo "  make shell-frontend  Open shell in frontend container"
	@echo "  make shell-postgres  Open psql in postgres container"
	@echo ""
	@echo "  make migrate         Run database migrations"
	@echo "  make seed            Run database seeders"
	@echo "  make fresh           Fresh migrate + seed"
	@echo "  make test            Run backend tests"
	@echo "  make lint-backend    Run PHP linting (pint)"
	@echo "  make lint-frontend   Run frontend lint"
	@echo ""
	@echo "  make artisan cmd=''  Run artisan command"
	@echo "  make npm cmd=''      Run npm command in frontend"
	@echo ""
	@echo "  make setup           First-time project setup"
	@echo ""

# ---- Core ----------------------------------------------------------------

up:
	docker compose up -d
	docker compose exec backend composer install

down:
	docker compose down

build:
	docker compose build --no-cache

restart: down build up

logs:
	docker compose logs -f

logs-backend:
	docker compose logs -f backend worker scheduler

# ---- Shells ---------------------------------------------------------------

shell-backend:
	docker compose exec backend sh

shell-frontend:
	docker compose exec frontend sh

shell-postgres:
	docker compose exec postgres psql -U $${DB_USERNAME:-slms} -d $${DB_DATABASE:-slms}

# ---- Database -------------------------------------------------------------

migrate:
	docker compose exec backend php artisan migrate

seed:
	docker compose exec backend php artisan db:seed

fresh:
	docker compose exec backend php artisan migrate:fresh --seed

# ---- Testing --------------------------------------------------------------

test:
	docker compose exec backend php artisan test --parallel

test-coverage:
	docker compose exec backend php artisan test --coverage

# ---- Code Quality ---------------------------------------------------------

lint-backend:
	docker compose exec backend ./vendor/bin/pint

lint-frontend:
	docker compose exec frontend npm run lint

# ---- Artisan / NPM Passthrough --------------------------------------------

artisan:
	docker compose exec backend php artisan $(cmd)

npm:
	docker compose exec frontend npm $(cmd)

prod-up:
	docker compose -f docker-compose.prod.yml up -d --build

prod-down:
	docker compose -f docker-compose.prod.yml down

prod-migrate:
	docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force

prod-key:
	docker compose -f docker-compose.prod.yml exec backend php artisan key:generate

prod-logs:
	docker compose -f docker-compose.prod.yml logs -f

# ---- First-time Setup -----------------------------------------------------

setup:
	@echo "→ Copying .env.example to .env..."
	@cp -n .env.example .env || true
	@echo "→ Building Docker images..."
	@docker compose build
	@echo "→ Starting services..."
	@docker compose up -d
	@echo "→ Installing backend dependencies (Composer)..."
	@docker compose exec backend composer install
	@echo "→ Waiting for PostgreSQL to be healthy..."
	@sleep 5
	@echo "→ Generating application key..."
	@docker compose exec backend php artisan key:generate
	@echo "→ Running migrations..."
	@docker compose exec backend php artisan migrate --seed
	@echo "→ Installing frontend dependencies..."
	@docker compose exec frontend npm install
	@echo ""
	@echo "✓ SLMS is ready!"
	@echo "  App:      http://localhost"
	@echo "  Mailpit:  http://localhost:8025"
	@echo "  MinIO:    http://localhost:9001"
	@echo ""
