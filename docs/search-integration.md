# Full-Text Search: Laravel Scout + Elasticsearch

## Overview

Full-text search across `Course` content using Laravel Scout. Postgres stays the source of
truth. The Scout driver is swapped per environment:

- **Dev**: `SCOUT_DRIVER=elastic` (`babenkoivan/elastic-scout-driver` v6) — Elasticsearch as a
  read-replica synced asynchronously via Horizon queue. This is the ES showcase.
- **Prod**: `SCOUT_DRIVER=database` — Scout's built-in database engine searches Postgres
  directly with `LIKE`. No ES container, no queue, no import, **$0 infra**. The `search()` call
  in `SearchController` and the entire frontend are identical; only the env var differs.

Because the same Scout API backs both, the app can legitimately be described as *using
Elasticsearch* (in development) while prod stays free.

**Scope:** only `Course` is searchable. Videos are part of a course (stored in the `videos`
table, exposed via the course detail page) and are intentionally **not** indexed — a search
returns courses, and clicking one opens `/courses/{slug}`. `Video` therefore has no
`Searchable` trait and no ES index.

## Packages

```
laravel/scout ^11.3
babenkoivan/elastic-scout-driver ^6.0   # pulls elasticsearch/elasticsearch v9.4.0
```

## Architecture

- **One index**: `courses` (Scout default, one index per searchable model), migration-tracked
  in `elastic/migrations/`
- **Sync mode**: queued (`SCOUT_QUEUE=true`) — writes never block on ES availability
- **Queue**: dedicated `search` queue processed by Horizon
- **Search endpoint**: `GET /search?q=` (stateless, rate-limited) — returns courses only
- **Production**: no Elasticsearch. `SCOUT_DRIVER=database` searches Postgres directly, so the
  `t3.micro` box runs no ES container, no `search`-queue index jobs, and needs no
  `elastic:migrate`/`scout:import`. (Managed ES — Elastic Cloud / AWS OpenSearch — was
  considered but rejected: Elastic Cloud is ~$95/mo, and AWS OpenSearch's free tier would force
  a driver migration since Elastic's v9 client refuses to connect to OpenSearch's forked API.)

## Key configuration

| Env var | Dev value | Purpose |
|---|---|---|
| `SCOUT_DRIVER` | `elastic` | Tells Scout to use the ES driver |
| `SCOUT_QUEUE` | `true` | Async index sync via Horizon |
| `ELASTIC_HOST` | `elasticsearch:9200` | ES host (no `http://` — client adds it) |

## Important v6 notes

- Driver targets **Elasticsearch 9.x** (not 8.x). Docker image: `elasticsearch:9.0.3`.
- `ELASTIC_HOST` format is `host:port` — no scheme prefix.
- Index mappings are mostly left to ES auto-mapping, **except `courses.tags`**, which has
  an explicit mapping — see "Index mappings" below for why.
- `config/elastic.scout_driver.php` only has one option: `refresh_documents`
  (set to `true` in tests to make indexing synchronous).

## Published config files

| File | Purpose |
|---|---|
| `config/scout.php` | Scout driver + queue settings |
| `config/elastic.client.php` | ES connection (`ELASTIC_HOST`) |
| `config/elastic.scout_driver.php` | `refresh_documents` toggle |
| `config/elastic.migrations.php` | Index mapping migrations path/table (`elastic/migrations/`) |

## Index mappings

Schema-as-code via [`babenkoivan/elastic-migrations`](https://github.com/babenkoivan/elastic-migrations)
(`elastic/migrations/`, run with `php artisan elastic:migrate`). **Every index we rely on has a
migration** so the full set is discoverable in source (no need to inspect a live cluster) and the
prod bootstrap is a single `elastic:migrate`:

| Migration | Index | What it defines |
|---|---|---|
| `..._create_courses_index_mapping` | `courses` | explicit `tags` keyword mapping + `lowercase_normalizer` (see below) |

Within the index, individual fields are still left to ES auto-mapping unless a field needs
non-default analysis. Today the only such field is `courses.tags`:

```php
$mapping->keyword('tags', ['normalizer' => 'lowercase_normalizer']);
```

**Why:** ES auto-mapped `tags` as a default `text` field, which runs it through the standard
analyzer. That analyzer splits on hyphens, so a compound tag like `machine-learning` gets
indexed as two independent tokens, `machine` and `learning`. Since `multi_match` with
`operator: and` only checks that both tokens appear *somewhere* in the field — not that they
came from the same tag — a course tagged `["machine-tools", "learning-path"]` would score
almost identically to one actually tagged `machine-learning` for a search of "machine-learning".

Mapping `tags` as `keyword` (with a lowercase normalizer) makes it match as whole values
instead of tokenized fragments. `SearchParametersFactory` queries it separately from
`title`/`description` via a `*substring*` wildcard (spaces folded to hyphens, so "machine
learning" still finds the `machine-learning` tag) — this keeps partial/substring recall while
requiring the match to come from a single tag value, not a stitched-together pair.

If a field ever needs special treatment, add its mapping to the relevant migration (or a new
one) in `elastic/migrations/` rather than relying on auto-mapping.

**Prod (database driver) difference:** `tags` is a `jsonb` column that Postgres can't `LIKE`, so
`Course::toSearchableArray()` omits `tags` when `config('scout.driver') === 'database'`. Prod
search therefore covers **title + description** only; the tags keyword/wildcard behavior above is
an Elasticsearch-only (dev) feature.

**Note on re-running:** each migration's `up()` does `Index::dropIfExists(...)` then recreates the
index, so applying it against an environment that already holds data **wipes that index** — always
`scout:import` afterwards. On a fresh environment (e.g. first prod deploy) the index doesn't exist
yet, so there's nothing to wipe.

## First-time setup (dev / any Elasticsearch environment)

> **Prod does not need this** — it runs `SCOUT_DRIVER=database` (no ES). The steps below apply
> only to environments using the `elastic` driver (i.e. local dev).

Run migrations **before** importing so each index is created with its migration's mapping. If you
import first, ES auto-creates the index with dynamic mapping (wrong `tags` type on `courses`), and
the later `elastic:migrate` drops/recreates it — forcing a needless reimport.

```bash
php artisan elastic:migrate                    # creates the courses index with the correct schema
php artisan scout:import "App\Models\Course"
```

## Bulk import / index rebuild

```bash
# Import all existing DB records into Elasticsearch
docker compose exec app php artisan scout:import "App\Models\Course"

# Flush (wipe) an index without deleting it
docker compose exec app php artisan scout:flush "App\Models\Course"
```

## Search endpoint

```
GET /search?q=python   # searches courses (title, description, tags)
```

Response shape:
```json
{
  "courses": [...]
}
```

Each course carries a `slug`; the frontend `SearchBox` navigates to `/courses/{slug}` on select.

## Implementation checklist

- [x] Course + Video migrations and models
- [x] Elasticsearch index migration for `courses` (`elastic/migrations/`)
- [x] Elasticsearch service in `docker-compose.override.yml`
- [x] Install packages + publish configs
- [x] Configure `scout.php`, `.env.example`
- [x] Add `Searchable` trait to Course (Video is intentionally not searchable)
- [x] Add `search` queue to Horizon config
- [x] `SearchController` + `routes/web.php`
- [x] Course detail page (`/courses/{slug}`) + `SearchBox` navigation
- [x] Bulk import docs in `CLAUDE.md`
- [x] Feature tests: search endpoint, course page, `SearchParametersFactory` unit, tags-mapping integration
