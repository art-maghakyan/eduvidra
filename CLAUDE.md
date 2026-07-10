# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All development runs inside Docker. The host machine has no PHP or Node installed.

```bash
# Start full dev stack (7 containers: app, nginx, queue, scheduler, redis, db, vite)
docker compose up -d

# Shell into app container
docker compose exec app bash

# Run all tests (uses in-memory SQLite, not the dev Postgres)
docker compose exec app php artisan test

# Run a single test file
docker compose exec app php artisan test tests/Feature/HealthEndpointTest.php

# Run a single test by name
docker compose exec app php artisan test --filter=test_health_endpoint_returns_ok

# Run Artisan commands
docker compose exec app php artisan <command>

# Laravel Pint (code style — must pass in CI)
docker compose exec app vendor/bin/pint

# Check style without fixing
docker compose exec app vendor/bin/pint --test

# Build frontend assets (production)
npm run build

# Fresh start (wipes DB volumes)
docker compose down -v && docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --seed

# Search: dev uses Elasticsearch (SCOUT_DRIVER=elastic). Prod runs no ES —
# SCOUT_DRIVER=database searches Postgres directly (same code/UI, $0 infra), so
# none of the elastic:migrate/scout:import steps below apply in prod. See
# docs/search-integration.md.

# Apply Elasticsearch index mappings (babenkoivan/elastic-migrations, tracked in elastic/migrations)
docker compose exec app php artisan elastic:migrate

# Seed search data (10 courses + 100 videos) and index courses into Elasticsearch
# (only Course is searchable; videos are course data and are not indexed)
docker compose exec app php artisan dev:seed-search
docker compose exec app php artisan scout:import "App\Models\Course"

# Re-index after a mapping change (elastic/migrations/*): rebuild just that index, then reimport.
# NOTE: elastic:migrate:fresh drops *every* index in the ES cluster (indexManager->drop('*')),
# not just the migrated one - use rollback+migrate instead to stay scoped to indices with migrations.
docker compose exec app php artisan elastic:migrate:rollback
docker compose exec app php artisan elastic:migrate
docker compose exec app php artisan scout:import "App\Models\Course"

# Re-index after model changes with no mapping change (wipe and reimport)
docker compose exec app php artisan scout:flush "App\Models\Course"
docker compose exec app php artisan scout:import "App\Models\Course"

# npm packages must be installed inside the vite container (isolated volume)
docker compose exec vite npm install
```

## Architecture

**Stack:** PHP 8.4 + Laravel 13, Vue 3 + Inertia.js + Tailwind CSS v3, PostgreSQL 17, Redis 7, Nginx, Docker Compose.

### Request lifecycle

Browser → Nginx → PHP-FPM (`app` container) → Laravel → Inertia → Vue 3 SPA (no full page reloads after first visit). The Vite dev server runs in its own container with HMR proxied through the app on port 8000.

### RBAC (authorization)

Authorization uses `spatie/laravel-permission`. Two roles and two permissions are defined as PHP enums:

- `App\Enums\RbacRole` — `admin`, `student`
- `App\Enums\RbacPermission` — `admin.access`, `users.manage`

Routes use `middleware(['role:...'])` or `middleware(['permission:...'])` guards. The admin area is prefixed `/admin` and guarded by `permission:admin.access`.

On the frontend, user roles and permissions are shared via Inertia's `HandleInertiaRequests` middleware as `auth.user.roles` and `auth.user.permissions`. The `AuthenticatedLayout.vue` exposes a `can(permission)` helper that checks `page.props.auth.user.permissions`.

RBAC constants are mirrored in `resources/js/constants/rbac.js` — keep them in sync with the PHP enums.

### Inertia + Vue pages

Pages live in `resources/js/Pages/` and are resolved by name (e.g. `Inertia::render('Admin/Dashboard')` maps to `Pages/Admin/Dashboard.vue`). Layouts are applied per-page using Vue's `defineOptions({ layout })` pattern. The `@` alias resolves to `resources/js/`.

### Two-factor authentication

Custom TOTP 2FA (not Fortify). After login, users with `two_factor_secret` set are redirected to `/two-factor/challenge`. New users who register are required to set up 2FA at `/two-factor/setup` before accessing protected routes. Admins can mark `two_factor_required = true` on any user and reset their 2FA.

### SEO

`App\Support\Seo::forRequest()` builds Open Graph + Twitter meta per route using `config/seo.php`. The result is shared as `seo` prop via Inertia middleware and rendered in `resources/views/app.blade.php`.

### Testing

Tests use an in-memory SQLite database (configured in `phpunit.xml`) — not the dev Postgres. Feature tests that need RBAC must seed roles/permissions via `RbacSeeder` or create them inline (the permission tables don't auto-migrate with in-memory SQLite unless migrations run). The test environment has `CACHE_STORE=array` and `QUEUE_CONNECTION=sync`.

### CI/CD

GitLab CI pipeline: pipelines run only on MRs and pushes to `master`. Stages: `build` (CI Docker image, only when `docker/ci/Dockerfile` changes) → `lint` (Pint) → `test` (PHPUnit) → `deploy` (SSH into EC2, runs `bin/deploy.sh`). The pre-built CI image is stored in GitLab Container Registry as `ci-php:8.4`.

### Production

Production uses `docker-compose.prod.yml` overlay on EC2 (`t3.micro`). The deploy script pulls latest code, rebuilds Docker images, runs migrations, clears/warms Laravel caches, and prunes old images. A blue/green deploy variant (`bin/deploy-blue-green.sh`) is available. The `GET /health` endpoint returns `200`/`503` JSON for deploy readiness checks.

Logs go to `stderr` in production; Docker ships them to CloudWatch Logs via the `awslogs` driver. AWS credentials are not in `.env` — the EC2 instance uses an IAM role for S3 and CloudWatch access.
