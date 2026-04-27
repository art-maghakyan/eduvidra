# Eduvidra

Eduvidra is a professional SaaS Learning Management System (LMS) designed for streamlined education management. It features a robust multi-role architecture for administrators and students.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4 + Laravel 11 |
| Frontend | Vue 3 + Inertia.js + Tailwind CSS + Vite |
| Database | PostgreSQL 17 |
| Cache / Queue | Redis 7 |
| Web server | Nginx 1.27 |
| Container runtime | Docker + Docker Compose |
| CI/CD | GitLab CI (shared runners) |
| Cloud | AWS — EC2 (`t3.micro`), RDS (Postgres), S3 (uploads), IAM |

## Project Structure

```
├── Dockerfile                   # Multi-stage: vendor, frontend, base, dev, app, nginx
├── docker-compose.yml           # Base services (app, queue, scheduler, redis)
├── docker-compose.override.yml  # Local dev (bind mounts, vite, local db, port 8000)
├── docker-compose.prod.yml      # Production overlay (named volumes, restart policies)
├── docker-compose.prod.local.yml # Local prod-stack testing (local db, prod build)
├── docker/
│   ├── ci/Dockerfile            # Pre-built CI image (PHP 8.4 + extensions + Composer)
│   ├── nginx/
│   │   ├── default.conf         # Dev Nginx config
│   │   └── prod.conf            # Production Nginx config
│   └── php/
│       └── opcache.ini          # PHP Opcache settings (prod)
├── bin/
│   └── deploy.sh                # Production deploy script (runs on EC2 via GitLab CI)
└── .gitlab-ci.yml               # CI/CD pipeline (build → lint → test → deploy)
```

## Local Development

### 1. Clone & configure environment

```bash
git clone https://gitlab.com/art-maghakyan/eduvidra.git
cd eduvidra
cp .env.example .env
```

Edit `.env` and set at minimum:
- `APP_KEY` — generated in step 3
- `DB_*` — already pre-filled for local Docker Postgres (no changes needed)

### 2. Build & start the full dev stack

```bash
docker compose up -d --build
```

This starts 7 containers: `app` (PHP-FPM dev), `nginx`, `queue`, `scheduler`, `redis`, `db` (local Postgres), `vite` (HMR dev server).

### 3. One-time initialization (fresh clone / after `down -v`)

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --seed
```

### 4. Open the app

App: **http://localhost:8000**
Vite HMR: **http://localhost:5173** (proxied automatically — no manual setup needed)

### Everyday commands

```bash
# Start dev stack
docker compose up -d

# Stop containers (keep DB data)
docker compose down

# Wipe everything including volumes (fresh start)
docker compose down -v

# Tail logs
docker compose logs -f app nginx vite

# Shell into app
docker compose exec app bash

# Run Artisan
docker compose exec app php artisan <command>

# Run tests
docker compose exec app php artisan test

# Rebuild images (after Dockerfile change)
docker compose up -d --build
```

## Testing the Production Build Locally

Use the `prod.local` overlay — it runs the production Docker targets with a local Postgres instead of RDS:

```bash
VITE_APP_VERSION=$(git rev-parse --short HEAD) \
  docker compose -f docker-compose.yml -f docker-compose.prod.yml -f docker-compose.prod.local.yml up -d --build
```

App available at **http://localhost** (port 80).

Clean up when done:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml -f docker-compose.prod.local.yml down -v
docker image rm eduvidra-app:latest eduvidra-nginx:latest 2>/dev/null || true
```

## Environment Variables

| File | Purpose |
|---|---|
| `.env.example` | Committed template for local dev |
| `.env.production.example` | Committed template for production |
| `.env` | Local only — **not committed** |
| `/opt/eduvidra/.env` | Lives on EC2 — managed manually |

Production secrets (RDS endpoint, `APP_KEY`, S3 bucket, Redis URL) are stored only in `/opt/eduvidra/.env` on the server. AWS credentials are **not** in `.env` — the EC2 instance uses an IAM role.

## CI/CD Pipeline

```
Feature branch push → no pipeline
MR opened / updated → lint + test
Merge to master → lint + test + deploy
```

### Stages

| Stage | Job | What it does |
|---|---|---|
| `build` | `build:ci-image` | Builds & pushes `ci-php:8.4` to GitLab Registry — only when `docker/ci/Dockerfile` changes |
| `lint` | `pint` | Runs Laravel Pint (code style check) |
| `test` | `phpunit` | Runs PHPUnit using the pre-built `ci-php:8.4` image |
| `deploy` | `deploy` | SSHs into EC2 and runs `bin/deploy.sh` |

Composer dependencies are cached between runs, keyed on `composer.lock`.

### Deploy script (`bin/deploy.sh`)

1. `git pull --ff-only`
2. Stamps `VITE_APP_VERSION` with the short commit SHA
3. `docker compose up -d --build --remove-orphans`
4. Waits for PHP-FPM to be ready
5. `php artisan migrate --force`
6. Clears and warms Laravel caches
7. Prunes stale Docker images

## AWS Architecture

```
Internet → Elastic IP → EC2 t3.micro (Ubuntu 24.04)
                              ├── Docker: nginx (port 80)
                              ├── Docker: app (PHP-FPM)
                              ├── Docker: queue
                              ├── Docker: scheduler
                              └── Docker: redis
                         ↓ (private subnet, SSL)
                        RDS PostgreSQL 17 (db.t3.micro)
                         ↓ (IAM role — no static keys)
                        S3 bucket (user uploads)
```

- **EC2** — `t2.micro` (free tier eligible), deploy user owns `/opt/eduvidra`
- **RDS** — `db.t3.micro`, `eu-central-1`, private subnets, SSL required
- **S3** — `eduvidra-app-uploads`, access via EC2 IAM role (no `AWS_ACCESS_KEY_ID` in `.env`)
- **Networking** — custom VPC, 2 public + 2 private subnets across 2 AZs

## Version Badge

Every page shows a small version stamp in the bottom-right corner. In dev it reads `vdev`. After each production deploy it shows the short git SHA of the deployed commit (e.g. `v7f3a912`), which is baked in at build time via `VITE_APP_VERSION`.
