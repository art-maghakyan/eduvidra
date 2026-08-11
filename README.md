# Eduvidra

A learning-management platform (courses, video lessons, student/admin roles) built as a
production-deployed Laravel application — not a tutorial project. It runs on AWS behind a
GitLab CI/CD pipeline, with queue workers, full-text search, transactional email, and
blue/green deploys.

**Live:** https://eduvidra.com · **Licence:** [MIT](LICENSE)

---

## What's actually interesting here

If you're evaluating this repo, these are the parts worth opening:

| Area | What it demonstrates | Where to look |
|---|---|---|
| **RBAC** | Roles/permissions as PHP enums rather than magic strings, enforced by route middleware and mirrored to the frontend through Inertia props | `app/Enums/RbacRole.php`, `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php` |
| **Two-factor auth** | Hand-rolled TOTP flow (not Fortify): QR setup, challenge, and admin-forced enrolment | `app/Http/Controllers/Auth/TwoFactor*.php`, `resources/js/Pages/Auth/TwoFactor*.vue` |
| **Swappable search** | Laravel Scout over Elasticsearch in dev and Postgres in production — identical controller and UI, one env var apart, so the showcase costs $0 to host | `app/Search/`, [docs/search-integration.md](docs/search-integration.md) |
| **Email reputation** | Queued mail via SES, with an SNS webhook that verifies message signatures **and** topic ARNs before writing to a suppression list | `app/Http/Controllers/SesWebhookController.php`, [docs/aws-ses-production-email.md](docs/aws-ses-production-email.md) |
| **Queues** | Redis-backed jobs supervised by Horizon, gated behind the same RBAC permission as `/admin` | `app/Providers/HorizonServiceProvider.php`, [docs/horizon-setup.md](docs/horizon-setup.md) |
| **Deployment** | Blue/green PHP-FPM switchover with health gating and a scripted rollback | `bin/deploy-blue-green.sh`, `bin/rollback-blue-green.sh` |

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4 · Laravel 13 |
| Frontend | Vue 3 · Inertia.js · Tailwind CSS · Vite |
| Database | PostgreSQL 17 |
| Cache / queue | Redis 7 · Laravel Horizon |
| Search | Laravel Scout — Elasticsearch (dev) / Postgres (prod) |
| Web server | Nginx 1.27 |
| Runtime | Docker · Docker Compose |
| CI/CD | GitLab CI |
| Cloud | AWS — EC2, RDS, S3, SES, CloudWatch, IAM |

## Architecture

Inertia means there's no separate API layer: Laravel controllers return Vue page components
directly, and the browser never does a full reload after the first paint.

```
Browser
   │
   ▼
Nginx ──► PHP-FPM (app) ──► Laravel ──► Inertia ──► Vue 3 SPA
                               │
                               ├──► PostgreSQL 17      (source of truth)
                               ├──► Redis 7            (cache, sessions, queue)
                               │       └──► Horizon ──► queue workers ──► SES
                               └──► Scout ──► Elasticsearch (dev) / Postgres (prod)
```

In production the same image runs on EC2, with Postgres on RDS and uploads on S3. AWS access
uses the instance's IAM role — there are no static AWS keys in any environment file.

```
Internet → Elastic IP → EC2 t3.micro (Ubuntu 24.04)
                            ├── nginx (:80)
                            ├── app (PHP-FPM, blue/green)
                            ├── queue (Horizon)
                            ├── scheduler
                            └── redis
                          ↓ private subnets, SSL required
                        RDS PostgreSQL 17 (db.t3.micro)
                          ↓ IAM role, no static keys
                        S3 (user uploads)
```

---

## Running it locally

Everything runs in Docker — you do **not** need PHP, Composer, or Node on your machine.

```bash
git clone https://gitlab.com/art-maghakyan/eduvidra.git
cd eduvidra
cp .env.example .env          # defaults already match the Docker services

docker compose up -d --build  # app, nginx, queue, scheduler, redis, db, vite

docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --seed
```

Open **http://localhost:8000**. Vite HMR is proxied automatically.

`migrate --seed` creates the roles and permissions. To register an admin, sign up through the
UI, then grant the role:

```bash
docker compose exec app php artisan tinker
>>> App\Models\User::first()->assignRole(App\Enums\RbacRole::ADMIN->value);
```

### Search (optional)

Search works out of the box against Postgres (`SCOUT_DRIVER=database`). To exercise the
Elasticsearch path instead, keep the default `SCOUT_DRIVER=elastic` and populate the index:

```bash
docker compose exec app php artisan elastic:migrate           # apply index mappings
docker compose exec app php artisan dev:seed-search           # 10 courses + 100 videos
docker compose exec app php artisan scout:import "App\Models\Course"
```

## Running the tests

```bash
docker compose exec app php artisan test                              # everything
docker compose exec app php artisan test tests/Feature/SesWebhookTest.php
docker compose exec app php artisan test --filter=test_health_endpoint_returns_ok
```

Tests run against in-memory SQLite with `CACHE_STORE=array` and `QUEUE_CONNECTION=sync`, so
they need no running database. The two Elasticsearch mapping tests skip themselves when no ES
node is reachable.

Code style is enforced in CI by Laravel Pint:

```bash
docker compose exec app vendor/bin/pint --test   # check
docker compose exec app vendor/bin/pint          # fix
```

### Everyday commands

```bash
docker compose up -d                  # start
docker compose down                   # stop, keep data
docker compose down -v                # stop, wipe volumes
docker compose logs -f app nginx vite # tail logs
docker compose exec app bash          # shell in
docker compose exec vite npm install  # npm lives in the vite container
```

---

## Project layout

```
app/
├── Enums/                    # RbacRole, RbacPermission, SuppressionType
├── Http/Controllers/         # Inertia pages, health, search, SES webhook
├── Search/                   # Scout query building, driver-agnostic
├── Support/Seo.php           # Per-route Open Graph / Twitter metadata
└── Providers/                # App, Horizon (RBAC gate), Search
resources/js/
├── Pages/                    # Resolved by name from Inertia::render()
├── Layouts/                  # Applied per page via defineOptions({ layout })
└── constants/rbac.js         # Mirrors the PHP enums
bin/                          # Deploy, blue/green, rollback, CloudWatch setup
docker/                       # Nginx configs, CI image, opcache tuning
elastic/migrations/           # Versioned Elasticsearch index mappings
docs/                         # Implementation write-ups per feature
```

## Environment configuration

| File | Purpose |
|---|---|
| `.env.example` | Committed template for local dev — matches the Compose services |
| `.env.production.example` | Committed template for production |
| `.env` | Local only, never committed |
| `/opt/eduvidra/.env` | Lives on the server, managed out of band |

No secrets are committed to this repository, and no AWS credentials exist in any `.env` —
production reads them from the EC2 instance role.

## CI/CD

```
Feature branch push → no pipeline
MR opened / updated → lint + test
Merge to master     → lint + test + deploy
```

| Stage | Job | What it does |
|---|---|---|
| `build` | `build:ci-image` | Rebuilds the `ci-php:8.4` image — only when `docker/ci/Dockerfile` changes |
| `lint` | `pint` | Laravel Pint style check |
| `test` | `phpunit` | Full suite on the pre-built CI image |
| `deploy` | `deploy` | SSH to EC2, run `bin/deploy.sh` |

Composer dependencies are cached across runs, keyed on `composer.lock`.

### Health endpoint

`GET /health` returns `200` with `{"ok": true, ...}` when the database and cache both respond,
and `503` otherwise. Deploy scripts gate the blue/green switch on it. Failure details are
logged rather than returned, since the endpoint is public.

### Blue/green deploys

`bin/deploy-blue-green.sh` keeps two PHP-FPM colours (`app-blue`, `app-green`) alive and points
Nginx at the active one via `/etc/nginx/conf.d/upstream-active.conf`. It builds the inactive
colour, runs `php artisan health:check` against it, migrates, warms caches, then switches with
a graceful reload. `bin/rollback-blue-green.sh` reverses it.

> Caveat, stated honestly: the Nginx image still bakes in built frontend assets, so Nginx can be
> recreated when assets change. The switch protects PHP-FPM traffic first; fully zero-downtime
> asset deploys would need versioned S3/CloudFront assets or a shared atomic asset directory.

### Testing the production build locally

```bash
VITE_APP_VERSION=$(git rev-parse --short HEAD) \
  docker compose -f docker-compose.yml -f docker-compose.prod.yml \
                 -f docker-compose.prod.local.yml up -d --build
```

Serves on **http://localhost**. Tear down with the same file list plus `down -v`.

## Monitoring

Laravel logs to `stderr` in production; Docker ships container logs to CloudWatch via the
`awslogs` driver. The CloudWatch Agent adds host memory, disk, and syslog/auth metrics, and
alarms cover EC2 health/CPU/memory/disk plus RDS CPU/storage/connections.

One-time setup, from a machine with AWS CLI access:

```bash
AWS_REGION=eu-central-1 ./bin/attach-cloudwatch-policy.sh
AWS_REGION=eu-central-1 RETENTION_DAYS=14 ./bin/configure-cloudwatch-log-groups.sh

AWS_REGION=eu-central-1 \
  INSTANCE_ID=i-xxxxxxxxxxxxxxxxx \
  DB_INSTANCE_IDENTIFIER=eduvidra-pg \
  ./bin/create-cloudwatch-alarms.sh    # add SNS_TOPIC_ARN=... to get notified
```

Then on the server, from `/opt/eduvidra`:

```bash
./bin/configure-prod-logging-env.sh
./bin/install-cloudwatch-agent.sh
./bin/deploy.sh
```

Log groups land under `/eduvidra/prod/*`.

## Version badge

Every page carries a small version stamp bottom-right: `vdev` locally, and the deployed short
commit SHA (e.g. `v7f3a912`) in production, baked in at build time via `VITE_APP_VERSION`.
