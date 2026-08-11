# Laravel Horizon Setup — Progress

Tracking the installation and configuration of Laravel Horizon to manage the
Redis-backed queue (currently serving the `WelcomeEmail` job). This document is
updated after each chunk of work.

## What Horizon gives us
- A `/horizon` dashboard for real-time queue metrics, throughput, and job history.
- Supervisor-managed worker pool (replaces the raw `queue:work` command).
- A UI to inspect and retry failed jobs (payload + stack trace), instead of
  reading the `failed_jobs` table and running `queue:retry` by hand.

## Progress checklist

- [x] **Chunk 0** — Create this progress doc.
- [x] **Chunk 1** — Install Horizon (`composer require laravel/horizon` + `horizon:install`).
  - Installed `laravel/horizon ^5.47`. Published `config/horizon.php` and
    `app/Providers/HorizonServiceProvider.php` (auto-registered in `bootstrap/providers.php`).
- [x] **Chunk 2** — Configure `config/horizon.php` supervisors (low min/max for t3.micro).
  - `defaults` supervisor: `balance=auto`, `minProcesses=1`, `tries=3`, `timeout=90`
    (matches the old `queue:work` flags). Production & local both capped at `maxProcesses=2`.
    Verified with `php artisan config:show horizon.environments`.
- [x] **Chunk 3** — Secure `/horizon` via the `viewHorizon` gate using `admin.access` permission.
  - Edited `HorizonServiceProvider::gate()` to return `$user?->can('admin.access')`,
    reusing the RBAC gate that guards `/admin`. Pint passes.
  - **Note:** Horizon bypasses this gate in the `local` environment, so `/horizon`
    is open to everyone in dev. The gate only enforces in non-local (production).
- [x] **Chunk 4** — Swap `queue:work` → `php artisan horizon` in Docker compose files.
  - `docker-compose.override.yml` (dev) and `docker-compose.prod.yml` (prod) `queue`
    service `command` now `php artisan horizon`. Recreated the dev container;
    `php artisan horizon:status` → "Horizon is running." `restart: unless-stopped`
    (prod) keeps it alive as a persistent process.
- [x] **Chunk 5** — Add `horizon:terminate` to `bin/deploy-blue-green.sh` for graceful deploys.
  - Added `horizon:terminate` (+ `sleep 5`) before the `queue` container restart so
    in-flight jobs drain instead of being killed mid-run. `bash -n` syntax check passes.
- [x] **Chunk 6** — Verify end-to-end (dashboard, gate, job processing) + Pint.
  - `/horizon` → 200, `/horizon/api/stats` → 200.
  - `viewHorizon` gate: admin → `true`, student → `false`, guest → `false`.
  - `horizon:supervisors` shows `supervisor-1` running, `redis:default (1)` worker, `auto`
    balancing — matches `minProcesses=1`.
  - Dispatched a test job: Horizon consumed and recorded it (proves the pipeline works);
    test artifact cleaned up afterward.
  - `vendor/bin/pint --test` → PASS (76 files).

## Notes
- Worker balancing: `auto` mode, `minProcesses=1`, `maxProcesses=2`. Kept low because
  production runs on a memory-constrained EC2 t3.micro shared with app/Nginx/Redis.
- Horizon runs as a persistent process via the existing `queue` container
  (`restart: unless-stopped`), not as a one-off command.

## Concepts (reference)

### What a "supervisor" is
In `config/horizon.php` a "supervisor" (e.g. `supervisor-1`) is a named worker group,
**not** Linux supervisord. It defines which queue(s) to watch, the process count range,
and the balancing strategy. `php artisan horizon` is the master process; it reads the
supervisor config and spawns/monitors child PHP worker processes accordingly.

### How worker balancing works
- `simple` — splits a fixed `processes` count evenly across queues.
- `auto` — watches queue depth and scales workers between `minProcesses` and
  `maxProcesses` (we use this). At idle it holds at 1; it adds a worker for bursts.
- `false` — static, no scaling.
`balanceMaxShift` caps how many processes it can add/remove per cycle; `balanceCooldown`
is the seconds between adjustments.

### Why min/max stay low
The t3.micro has ~1 GB RAM shared with the web app, Nginx and Redis, and each PHP worker
is ~25–40 MB. `minProcesses=1, maxProcesses=2` keeps one worker always warm without
starving the web process. Raise `maxProcesses` later if the queue backs up under load.

### Horizon as a persistent Docker process
`php artisan horizon` is long-running — it must stay up, not run once. The `queue`
container's `command` is now `php artisan horizon`, and Docker's `restart: unless-stopped`
(prod) restarts it if it dies. No separate supervisord needed; Docker is the process
manager. On deploy, `bin/deploy-blue-green.sh` calls `horizon:terminate` first so the
master finishes in-flight jobs and exits cleanly before the container is recreated.

### Viewing & retrying a failed job from the dashboard
1. Open `/horizon` → **Failed Jobs** in the sidebar.
2. Click a job to see its full payload, exception class, stack trace, queue, and
   failure time — all in the UI.
3. Click **Retry** to re-queue that single job (or **Retry All** for the whole list).
   Horizon also keeps ~1h of completed-job history with processing times.

**vs. the manual `failed_jobs` approach we used yesterday:**
| Manual | Horizon |
|--------|---------|
| `php artisan queue:failed` — flat table, no payload/trace detail | Click-through UI with full exception + stack trace |
| `php artisan queue:retry {uuid}` / `queue:retry all` by hand | One-click Retry / Retry All buttons |
| No view of successful jobs or throughput | Live metrics, recent jobs, per-queue wait times |

Note: Horizon records failures in **Redis** (its own store), separate from the
`failed_jobs` DB table. `queue:flush` clears the DB table; use the dashboard or
`horizon:forget {id}` to clear Horizon's copy.

### Local vs production access
In `APP_ENV=local`, Horizon **bypasses** the `viewHorizon` gate, so `/horizon` is open
in dev. In production the gate enforces `admin.access` — non-admins and guests get 403.
