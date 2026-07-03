# Smart Lab Management System (SLMS)

Enterprise platform untuk mengelola berbagai laboratorium dalam satu sistem terpadu.

## Stack

| Layer | Teknologi |
|---|---|
| Frontend | Vue 3, Vite, TailwindCSS, TypeScript, shadcn-vue |
| Backend | Laravel 12, PHP 8.3, REST API, Sanctum |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis 7 |
| Storage | MinIO (S3-compatible) |
| Realtime | Laravel Reverb |
| Reverse Proxy | Nginx |
| Container | Docker Compose |
| Mail (dev) | Mailpit |

## Struktur Monorepo

```
slms/
├── backend/              Laravel 12 application
├── frontend/             Vue 3 application
├── docker/
│   ├── backend/          PHP Dockerfile, php.ini, php-fpm config
│   ├── frontend/         Node Dockerfile, Nginx SPA config
│   ├── postgres/         Init scripts
│   └── redis/            Redis config
├── nginx/                Reverse proxy config
├── .github/workflows/    CI pipelines
├── docker-compose.yml    Development environment
├── Makefile              Developer shortcuts
└── .env.example          Environment template
```

## Quick Start

### Prasyarat

- Docker 24+
- Docker Compose v2
- Make

### Setup

```bash
# Clone repository
git clone <repo-url> slms
cd slms

# Setup otomatis (copy .env, build, migrate, seed)
make setup
```

### Akses Layanan

| Service | URL |
|---|---|
| Aplikasi | http://localhost |
| Mailpit (email) | http://localhost:8025 |
| MinIO Console | http://localhost:9001 |

### Perintah Umum

```bash
make up              # Jalankan semua service
make down            # Hentikan semua service
make logs            # Lihat log semua service
make shell-backend   # Masuk ke shell backend
make fresh           # Fresh migrate + seed
make test            # Jalankan test
make artisan cmd="route:list"   # Jalankan artisan command
make npm cmd="run build"        # Jalankan npm command
```

## Development

### Branch Strategy

```
main          Production-ready code
develop       Integration branch
feature/*     Feature branches
fix/*         Bug fixes
hotfix/*      Production hotfixes
```

### Commit Convention

```
feat:     Fitur baru
fix:      Bug fix
chore:    Maintenance
docs:     Dokumentasi
test:     Testing
refactor: Refactoring
ci:       CI/CD changes
```

## Arsitektur

Lihat dokumen arsitektur di `docs/` (akan dibuat secara bertahap).

## Ports

| Port | Service |
|---|---|
| 80 | Nginx (App) |
| 5432 | PostgreSQL |
| 6379 | Redis |
| 8025 | Mailpit Web UI |
| 8080 | Laravel Reverb |
| 9000 | MinIO S3 API |
| 9001 | MinIO Console |
