# AI Chat Tutor (Anthropic API)

**Status:** in progress — Steps 0, 1 and 3 done. Step 2 is next and is the first billed step. This document is the
context record; re-read it before starting or resuming work.

**Last updated:** 2026-08-18

## Current state (2026-08-18)

| | |
|---|---|
| **Done** | **Step 1** — config, SDK, driver seam. Fake driver streamed 63 chunks and returned a `ChatResult`; `driver=real` resolves `AnthropicChatClient` holding a real `Anthropic\Client`.<br>**Step 3** — `conversations` + `messages` schema, models, 7 feature tests. Verified on SQLite *and* a throwaway Postgres 17. Suite: 87 passed / 2 pre-existing skips; Pint clean on 107 files. |
| **Gate passed** | **Step 0** — `200` + `Eduvidra API OK` from **both** host and `app` container, 2026-08-18. Model alias resolved to `claude-haiku-4-5-20251001`. Cost: ~$0.00014 for both calls (17 in / 9 out each). Container egress, CA certs and the `.env`-sourcing command all confirmed working. |
| **Unblocked next** | Step 4 (persistence wired in) and Step 8 (policy + rate limiting) — both $0. |
| **Budget** | $5 of prepaid credit, added 2026-08-18. Auto-reload **off**, so the balance is a hard ceiling. At ~$0.004/turn that is ~1,200 turns. `ANTHROPIC_DRIVER` stays `fake` outside deliberate billed runs. |

## Overview

An AI study assistant on the course detail page. A student types a question, Claude answers
in a streaming reply. The course's metadata (title, description, tags, lesson titles) is
injected into the prompt as framing, so the assistant knows *what the student is studying*
even though it cannot see the lesson content itself.

Interaction is **student ⇄ AI only** — no human teacher in the loop, no moderation queue,
no handoff. "Tutor" throughout this document names the persona the model adopts, not a
person.

All Anthropic API facts here were verified against `platform.claude.com` on **2026-08-17**.
Pricing and model IDs change; re-verify before trusting them.

## Product decisions

| Decision | Choice | Why |
|---|---|---|
| Purpose | Context-aware study tutor | Framing by course metadata makes it Eduvidra-specific rather than a generic Claude wrapper |
| Grounding | Metadata only, no retrieval | No transcripts exist (see Constraints) |
| Scope unit | Thread per `(user, course)` | There is no video page for a student to be "on" (see Constraints) |
| Persistence | Postgres, survives sessions | Enables debugging bad answers and auditing abuse — cannot be added retroactively |
| Access | `role:student` + `verified` | Every request attributable to a user |
| Quota | 10 messages/day/user | Abuse control; ~$0.04/user/day at the cap |
| Language | Mirrors the student's | One line in the system prompt; no locale system needed |
| Model | `claude-haiku-4-5` | Cheapest; ~$0.004/turn |
| Streaming | SSE, not WebSockets | One-directional, short-lived, plain HTTP — no extra process or port |

## Constraints that shaped the design

These are properties of the current codebase, not preferences. Both are the reason the
feature is scoped the way it is.

### 1. There is no lesson content

`videos` has only `title`, `description`, `duration_seconds`, `is_published`,
`published_at`, `course_id`. There is **no transcript, no caption, no lesson body, and no
video URL column**. Seeded descriptions are a single sentence
(`app/Console/Commands/SeedSearchData.php:22`).

So the tutor is *framed* by metadata, not *grounded* in content. It will know a student is
on "Loops: for and while in Depth" in a Python course; it will not know what the instructor
said. **The system prompt states this explicitly**, so the model says "I don't have the
video transcript" rather than inventing a claim about the lesson.

### 2. There is no video page, and nothing is watchable

`/courses/{course:slug}` is the only course route (`routes/web.php`), and
`Courses/Show.vue:46-53` renders lesson titles as a plain non-clickable `<ol>`. There is no
`/videos/{id}` route and no player.

Consequence: threads are scoped to the **course**, with a nullable `video_id` held in
reserve so per-lesson threads need no migration later.

Transcripts are downstream of this: there is no video asset to transcribe yet.

### 3. There is no chat UI

Nothing in `resources/js/Pages/` renders a conversation. The entire interface is built by
this feature (Steps 2 and 6), following existing conventions — `<script setup>`,
Composition API, Tailwind with dark-mode variants.

## Anthropic Console setup

### Account

The API lives in the **Claude Console** (<https://platform.claude.com>) — a different
product from the claude.ai chat subscription. The same Anthropic login works for both; the
*identity* is shared, the **billing is not**. A Pro/Max subscription grants **zero** API
credit.

New organisations receive a small amount of free credits (amount not published — check
**Settings → Billing**). They are ordinary prepaid credits, they expire, and when exhausted
requests fail with a billing error rather than overcharging.

### Workspace and key

Create the workspace **before** the key — keys cannot be moved between workspaces.

1. **Settings → Workspaces** → `eduvidra-dev` (own keys, usage reporting, and limits)
2. **Settings → API keys** → create, scoped to that workspace
3. Choose an expiration: presets are 3h / 1d / 7d / 30d, plus custom or Never. **Fixed at
   creation, cannot be changed.** Expired keys return `401` and cannot be reactivated. Use
   **30 days** for dev keys; `Never` only for keys in a real secrets manager.

### Spend limits — three layers

| Layer | Where | Stops |
|---|---|---|
| Org monthly limit | Settings → Billing → Spend limits | Long-run overspend. Set **$20–25** in dev. Usage pauses until next month when hit |
| Workspace rate limits | Workspace settings (RPM / ITPM / OTPM) | **Runaway loops.** Set RPM ~20 in dev |
| Per-user daily quota | Our code (Step 7) | One abusive user consuming everyone's budget |

Tier caps: Start $500, Build $1,000, Scale $200,000. New orgs start on **Start**.

The middle layer matters most: a monthly cap does nothing against a loop that burns $20 in
ninety seconds. Note that limits **cannot** be set on the Default workspace — hence step 1.

## Model choice

| Model | ID | Input /MTok | Output /MTok | Context | Max output |
|---|---|---|---|---|---|
| **Haiku 4.5 (chosen)** | `claude-haiku-4-5` | $1 | $5 | 200K | 64K |
| Sonnet 5 | `claude-sonnet-5` | $2 | $10 | 1M | 128K |
| Opus 5 | `claude-opus-5` | $5 | $25 | 1M | 128K |

Cost per turn assumes ~1,750 input + ~500 output tokens:

| Model | Per turn | 100 dev turns | 10-msg daily cap |
|---|---|---|---|
| **Haiku 4.5** | **~$0.004** | **~$0.40** | **~$0.04/user/day** |
| Sonnet 5 | ~$0.0085 | ~$0.85 | ~$0.085/user/day |
| Opus 5 | ~$0.021 | ~$2.10 | ~$0.21/user/day |

**Same model in dev and prod.** "Cheap in dev, good in prod" means tuning prompts against
one model and shipping to another, then re-debugging in production. Dev cost is controlled
by the spend limit and the fake driver instead.

### Three consequences of choosing Haiku 4.5

1. **Prompt caching is inert at our prompt size.** Minimum cacheable prefix is **4,096
   tokens on Haiku 4.5** (vs 512 on Opus 5). Our prefix is ~700 tokens, so a `cacheControl`
   breakpoint would be **silently ignored** — no error, just `cache_creation_input_tokens:
   0` forever. We therefore do *not* implement caching; we keep the instrumentation that
   will show when it becomes worthwhile. Padding the prompt to 4,096 tokens to "unlock"
   caching would cost more than it saves.
2. **No adaptive thinking.** Haiku 4.5 supports only the older
   `thinking: {type: "enabled", budget_tokens: N}` form. We omit `thinking` entirely, which
   suits a low-latency chat. **Do not copy `thinking: {type: "adaptive"}` from Opus/Sonnet
   examples — it is not supported and will error.**
3. **200K context / 64K max output**, not 1M/128K. Irrelevant at 10 messages/day.

Upgrading to `claude-sonnet-5` is a one-env-var change. Decide it on real answer quality
once Step 6 runs, not in advance.

## Key storage

```
ANTHROPIC_API_KEY=sk-ant-api03-...
ANTHROPIC_MODEL=claude-haiku-4-5
ANTHROPIC_DRIVER=fake        # fake | real
# ANTHROPIC_MAX_TOKENS=1024  # optional; config default is 1024
```

- `.env.example` carries the same keys with an empty `ANTHROPIC_API_KEY`. Both files were
  updated on 2026-08-18; `.env` is gitignored (`.gitignore:4`).
- `config/anthropic.php` exposes `driver`, `api_key`, `model`, `max_tokens`.
  `daily_message_limit` is deliberately *not* there yet — it arrives with Step 7.
- **`env()` is called only in `config/anthropic.php`.** Production runs `config:cache`,
  after which `env()` returns `null` everywhere else. All other code reads
  `config('anthropic.*')`.
- **The key never reaches the browser.** No Inertia shared prop; no `VITE_`-prefixed
  variable (Vite inlines those into the JS bundle, making them public); no client-side
  calls to `api.anthropic.com`. Vue talks only to our own routes.
- Prod: server `.env`, or AWS Secrets Manager (an IAM role already exists for S3/CloudWatch).
  **Not** in GitLab CI variables — tests never hit the real API.

## Developing at near-zero cost

**There is no Anthropic sandbox, test mode, or free developer tier.** Every call to
`api.anthropic.com` is metered. Four things make that a non-issue:

1. **The fake driver** (Step 1). Every API call sits behind one interface with two
   implementations selected by `ANTHROPIC_DRIVER`. `fake` returns canned chunks with
   simulated delays and makes **zero network calls** — covering ~90% of the build (SSE
   plumbing, Vue, schema, queues, rate limits, budgets) at **$0**.
2. **Token counting is free.** `POST /v1/messages/count_tokens` is explicitly free, on a
   rate-limit pool separate from message creation. The whole prompt-assembly pipeline can
   be verified against exact real token counts without paying for a completion.
3. **Tests never touch the network** (Step 9), so CI is free permanently.
4. **Real calls are ~$0.004 each** on Haiku 4.5.

## Implementation steps

Each step is independently runnable and ends in something observable. Do not start a step
before the previous one's verification passes.

| # | Step | Output | Cost | Status |
|---|---|---|---|---|
| 0 | Console setup + smoke test | `200` + text from host **and** container | <$0.01 | **Done** 2026-08-18 (~$0.0001) |
| 1 | Config, SDK, driver seam | Fake chunks in tinker | $0 | **Done** 2026-08-18 |
| 2 | One non-streaming round trip | Answer appears in browser | ~$0.01 | **Ready — next billed step** |
| 3 | Conversation schema | Feature test passes | $0 | **Done** 2026-08-18 |
| 4 | Persistence wired in | History survives reload | $0 | **Ready — next** |
| 5 | Course context injection | `count_tokens` prints prompt size; non-English question gets same-language reply | $0 | Ready |
| 6 | Streaming via SSE | Text streams progressively in Docker | ~$0.05 | Ready after Step 5 |
| 7 | Cost control | 11th message/day returns 429 | <$0.05 | Ready after Step 6 |
| 8 | Security | Rate limit trips; cross-user access 403s | $0 | Ready |
| 9 | Tests | `artisan test` green, zero network calls | $0 | Partly done (see Step 1) |
| 10 | Async + observability | Titles via Horizon; CloudWatch metrics | small | Ready after Step 9 |

### Step 0 — Smoke test (hard gate)

Nothing gets built until this returns `200` with real text, from **both** the host and the
`app` container. The container test additionally proves outbound HTTPS, valid CA certs, and
that the env var is readable there.

```bash
ANTHROPIC_API_KEY=sk-ant-... curl -sS https://api.anthropic.com/v1/messages \
  -H "x-api-key: $ANTHROPIC_API_KEY" \
  -H "anthropic-version: 2023-06-01" \
  -H "content-type: application/json" \
  -d '{"model":"claude-haiku-4-5","max_tokens":64,"messages":[{"role":"user","content":"Reply with exactly: Eduvidra API OK"}]}'
```

| Response | Meaning |
|---|---|
| `401 authentication_error` | Key wrong, unset, or expired |
| `400 invalid_request_error` | Malformed JSON (usually shell quoting) |
| `403 permission_error` | Key lacks model access |
| `404 not_found_error` | Model ID typo — must be exactly `claude-haiku-4-5` |
| `429 rate_limit_error` | Wait `retry-after` seconds |

**Container half — the command in the original plan does not work.** Dev's `app` service
gets an explicit `environment:` map (the `x-app-environment` anchor in
`docker-compose.override.yml`) and **no `env_file:`**, so `$ANTHROPIC_API_KEY` is empty in a
container shell even with the key in `.env`. Laravel is unaffected — `env()` parses
`/var/www/.env` through the bind mount — and prod differs again, using `env_file: - .env`.
Source the file inside the command:

```bash
docker compose exec app sh -c 'set -a; . /var/www/.env; set +a; curl -sS ...'
```

`curl` is present in the `dev` stage (`Dockerfile:85-89`), so no image change is needed.

**Outcome 2026-08-18 — PASSED.** Both halves returned `200` with exactly
`Eduvidra API OK`; the alias resolved to `claude-haiku-4-5-20251001`, which also confirms
the model ID. 17 input / 9 output tokens per call, ~$0.00014 for the pair.

The first attempt, before credit was added, failed on billing — recorded here because the
`400`-vs-`401` distinction is worth not re-deriving. The host call returned:

```
400 {"type":"invalid_request_error","message":"Your credit balance is too low to access
the Anthropic API. Please go to Plans & Billing to upgrade or purchase credits."}
```

A wrong or expired key returns `401 authentication_error`; a `400` with this message means
the key is fine and only the balance is not. $5 of credit was added on 2026-08-18 and both
halves passed immediately afterwards.

### Step 1 — The driver seam

The most important architectural decision, made first: **nothing calls the SDK directly.**

```
composer require "anthropic-ai/sdk"      # Guzzle 7 already in composer.lock
```

- `config/anthropic.php` — the only file calling `env()`
- `App\Services\Ai\ChatClient` — interface
- `App\Services\Ai\AnthropicChatClient` — real, wraps `$client->messages->createStream()`
- `App\Services\Ai\FakeChatClient` — canned chunks + `usleep()`
- Bound in `AppServiceProvider` on `config('anthropic.driver')`

The PHP SDK uses **camelCase** named arguments (`maxTokens:`, not `max_tokens:`) even
though the wire format is snake_case.

#### As built (2026-08-18) — installed SDK `anthropic-ai/sdk ^0.42.0`

Every symbol below was read out of `vendor/anthropic-ai/sdk` rather than assumed.

- **One method, not two.** `ChatClient::stream(string $system, array $messages): Generator`
  yields text fragments and **returns** a `ChatResult` via `$stream->getReturn()`. Step 2's
  non-streaming round trip just concatenates the same generator, so there is no second code
  path to keep in sync, and Step 7's token accounting falls out of the same call.
- `App\Services\Ai\ChatResult` — readonly value object: `model`, `inputTokens`,
  `outputTokens`, `cacheCreationTokens`, `cacheReadTokens`, `stopReason`. Maps 1:1 onto the
  Step 3 `messages` columns.
- **User text never enters `$system`.** The signature keeps them apart by construction —
  the prompt-injection posture from Step 8, enforced at the seam.
- `ANTHROPIC_DRIVER=fake` and an empty `ANTHROPIC_API_KEY` are now forced in `phpunit.xml`
  (part of Step 9, done early so no test can ever spend money).

SDK surface, verified:

| Call | Shape |
|---|---|
| Client | `new Anthropic\Client(apiKey: ...)` |
| Stream | `$client->messages->createStream(maxTokens:, messages:, model:, system:)` → `IteratorAggregate` |
| First event | `RawMessageStartEvent` → `->message->usage` (`inputTokens`, `cacheCreationInputTokens`, `cacheReadInputTokens`), `->message->model` |
| Text | `RawContentBlockDeltaEvent` → `->delta`; only `TextDelta` carries `->text` |
| Last event | `RawMessageDeltaEvent` → `->usage->outputTokens` (cumulative), `->delta->stopReason` |

Unknown event types fall through and are ignored — `ping` arrives at any point and new types
get added over time.

### Step 3 — Schema

- `conversations` — `id`, `user_id` (FK cascade), `course_id` (FK cascade), **`video_id`
  (FK nullable cascade — forward seam, always null in v1)**, `title` nullable,
  `last_message_at`, timestamps.
- `messages` — `id`, `conversation_id` (FK cascade), `role`, `content`, `input_tokens`,
  `output_tokens`, **`cache_creation_tokens`**, `cache_read_tokens`, `model`,
  `stop_reason`, timestamps. Index `(conversation_id, id)`.

Token columns exist from day one — retrofitting cost accounting later means no history to
analyse.

#### Correction (2026-08-18): the unique index has to be two partial indexes

This document previously specified a single unique index on
`(user_id, course_id, video_id)` "so load-or-create is race-safe". **That does not work.**
SQL treats NULLs as distinct in a unique index, and `video_id` is null on every v1 row — so
the index permits unlimited duplicate threads for the same `(user, course)`. Confirmed
empirically on both SQLite and Postgres 17.

The fix is a pair of partial unique indexes, identical syntax on both drivers:

```sql
CREATE UNIQUE INDEX conversations_user_course_unique
  ON conversations (user_id, course_id) WHERE video_id IS NULL;

CREATE UNIQUE INDEX conversations_user_course_video_unique
  ON conversations (user_id, course_id, video_id) WHERE video_id IS NOT NULL;
```

This enforces one thread per `(user, course)` today and one per
`(user, course, video)` once per-lesson threads ship — the forward seam survives, still with
no migration needed later.

`cache_creation_tokens` was also missing from the original column list. Step 7 requires
summing `input + cache_creation + cache_read`, so all three are present from the start even
though caching is inert at our prompt size.

#### As built (2026-08-18)

- `database/migrations/2026_08_18_000001_create_conversations_table.php`
- `database/migrations/2026_08_18_000002_create_messages_table.php`
- `App\Models\Conversation`, `App\Models\Message`
- `tests/Feature/AiChatSchemaTest.php` — 7 tests

**Verified on both drivers.** Tests run on in-memory SQLite (87 passed suite-wide, Pint
clean on 107 files). Because CI only ever sees SQLite and prod is Postgres, the migration
was additionally run against a throwaway Postgres 17 container: both partial indexes were
created as written, the duplicate course-level thread was rejected
(`duplicate key value violates unique constraint "conversations_user_course_unique"`), and a
per-lesson thread alongside it was accepted.

The regression test was also confirmed to have teeth — swapping the migration back to the
original single composite index makes
`test_a_user_cannot_have_two_threads_on_the_same_course` fail, and only that test.

### Step 6 — Streaming, and the local-Docker traps

**SSE, not WebSockets:** one-directional, short-lived, plain HTTP. No extra process, no
Reverb/Echo, no new port through Cloudflare, native reconnect. WebSockets would be
justified by multi-user presence, which this has none of.

Laravel 13 provides `response()->eventStream()`
(`Illuminate/Routing/ResponseFactory.php:131`). It handles SSE framing, calls
`ob_flush()`/`flush()` per event, breaks on `connection_aborted()`, and **already sets**
`Content-Type: text/event-stream`, `Cache-Control: no-cache`, and **`X-Accel-Buffering:
no`** — the header nginx honours to disable buffering. Half the buffering problem is solved
for free.

Fixes still required in this repo:

| Layer | Problem | Fix |
|---|---|---|
| nginx | `docker/nginx/default.conf` sets no `fastcgi_read_timeout` → defaults to **60s**, killing long generations | Add `fastcgi_read_timeout 300s;` to the `\.php$` location |
| nginx | gzip re-introduces buffering even with `X-Accel-Buffering: no` | Confirm gzip off for the SSE route |
| PHP | `output_buffering` holds output | Set `output_buffering=Off` |
| PHP-FPM | `max_execution_time` shorter than a generation | Raise for this route |

**Error-handling trap:** `eventStream()` wraps the generator in
`try { … } catch (Throwable $e) { report($e); }` (`ResponseFactory.php:180`). A mid-stream
failure is logged and the stream simply *ends* — the browser sees a clean close and no
error. We must catch inside our generator and `yield` an explicit `error` event first.

Per the Anthropic SSE spec: `ping` events arrive any number of times at any point, and a
mid-stream `event: error` (e.g. `overloaded_error`) can arrive **after** the `200 OK`.
Unknown event types must be ignored, not thrown on — new ones get added over time.

### Step 7 — Cost control

- **No prompt caching in v1**, deliberately (see Model choice). The `cache_read_tokens`
  column exists so enabling it later is one line plus a number that proves it worked.
- **Token accounting** per message. When caching *is* active, `input_tokens` counts only
  tokens after the last cache breakpoint — total input is
  `input + cache_creation + cache_read`. Sum all three from the start.
- **Daily quota** from `config('anthropic.daily_message_limit')`, checked *before* the
  request; over budget returns a friendly 429 naming the reset time rather than calling the
  API.
- **Context growth**: cap replayed history by *token count*, not message count; summarise
  the dropped prefix. Rarely fires at 10 messages/day against a 200K window, but the
  failure mode when missing is a silently truncated conversation that gets subtly worse
  rather than erroring.
- **Bound `max_tokens`** per reply (~1,024).

### Step 8 — Security

- **Rate limiting** — this repo currently has **no** `RateLimiter::for` definitions at all.
  Add an `ai-chat` limiter keyed by **user ID, not IP** (students share NATs), plus a burst
  limiter.
- **Prompt injection** — users control the input, so treat it as hostile: instructions stay
  in the `system` parameter (never concatenate user text into it); course context is wrapped
  in delimiters framed as reference data, not instructions; and — the part that actually
  matters — **the endpoint gets no capabilities worth hijacking**. No tools, no DB access,
  no file access in v1. The worst case for a successful injection is a rude answer.
- **Authorisation** — an `AiConversationPolicy` verifying the thread belongs to the
  requester. Without it, `/chat/{conversation}/stream` leaks other students' threads by ID
  enumeration.
- Validate and cap input length in a Form Request before it costs tokens.

### Step 9 — Testing without the real API

- `FakeChatClient` bound in the test environment; add `ANTHROPIC_DRIVER=fake` to
  `phpunit.xml` (which already sets `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`) so no
  test can accidentally spend money.
- `Http::fake()` with a **canned SSE body** including `ping` and mid-stream `error` events,
  to prove the parser handles both.
- Feature tests: budget exhaustion → 429; another user's conversation → 403;
  unauthenticated → 302; injection-shaped input stays inside its delimiters.
- One **opt-in** live smoke test, skipped unless an env flag is set.

### Step 10 — Async and observability

- New Horizon queue `ai` (existing supervisors cover `default`, `emails`, `reports`,
  `search` — `config/horizon.php:202+`).
- **Queued**: title generation, history summarisation (same model — no second one to
  configure). **Never queued**: the chat reply itself — the user is waiting on it, and a
  queued job cannot stream to an open HTTP connection.
- CloudWatch metrics: tokens/day, cost/day, p95 latency.

## Environment notes

- **Port 5432 collides.** A host process listens on `127.0.0.1:5432` while
  `docker-compose.override.yml` publishes `5432:5432`, so `docker compose up -d` fails to
  start the `db` container. Step 1 needed no database and was run with
  `docker compose up -d --no-deps app`. **Step 3 onward needs this resolved** — either stop
  the host Postgres or republish as `5433:5432`.
- `composer audit` reports 30 advisories across 10 packages. Pre-existing, unrelated to this
  feature, but worth a pass at some point.

## Out of scope for v1

Transcript ingestion, vector search, tool use / function calling, multi-agent, quizzes and
progress tracking, WebSockets, and the lesson page / video player.

**Elasticsearch is not used by this feature.** Prod runs `SCOUT_DRIVER=database` with no ES
cluster, and course-title matching adds nothing over directly loading the course the
student is already on. See `search-integration.md`.

Natural follow-on order: lesson page + player → per-lesson threads (flip `video_id` on, no
migration) → transcripts → retrieval.

## Decision log

| Date | Decision | Rationale |
|---|---|---|
| 2026-08-17 | Metadata framing, not transcript retrieval | No transcripts exist; nothing is watchable yet |
| 2026-08-17 | Course-scoped threads, nullable `video_id` | No video page exists to scope per-lesson threads to |
| 2026-08-17 | Persist conversations | Enables debugging and auditing; cannot be added retroactively |
| 2026-08-17 | Students only, 10 msg/day | Attributable requests; abuse control rather than budget control |
| 2026-08-17 | `claude-haiku-4-5` | Cheapest at ~$0.004/turn. Accepted cost: no prompt caching, no adaptive thinking |
| 2026-08-17 | No prompt caching in v1 | 4,096-token minimum vs our ~700-token prefix — would be silently inert |
| 2026-08-17 | SSE over WebSockets | One-directional and short-lived; no extra process or port |
| 2026-08-17 | Same model dev and prod | Avoids tuning prompts against a model you don't ship |
| 2026-08-18 | **Rejected a Node.js SSE sidecar** | Laravel sessions are DB-backed with an encrypted cookie, so Node would need a bespoke auth bridge — plus a second deployable, test stack, CI lane and lint config. The real win (no PHP-FPM worker held per open connection) does not matter at 10 msg/day. Revisit only if concurrency becomes real |
| 2026-08-18 | `ChatClient::stream()` is the single seam | Yields text, returns `ChatResult` via `getReturn()`. Avoids a separate non-streaming path in Step 2 and a separate accounting hook in Step 7 |
| 2026-08-18 | `phpunit.xml` pins `fake` now, not in Step 9 | One line; makes it impossible for any test written between now and Step 9 to spend money |
| 2026-08-18 | **Two partial unique indexes, not one composite** | A single `UNIQUE(user_id, course_id, video_id)` enforces nothing while `video_id` is null on every row — SQL treats NULLs as distinct. See the correction under Step 3 |
| 2026-08-18 | Schema changes are verified against Postgres, not just SQLite | Tests run on SQLite but prod is Postgres, and the index is raw DDL. A throwaway PG 17 container is the cheap way to close that gap |

## Open questions for later

- Is 10 messages/day too tight in practice? Watch `UserAiUsage` after launch — raise it if
  genuine students hit the ceiling. The cost argument for keeping it low is weak on Haiku
  (~$0.04/user/day at the cap); it is an abuse control, not a budget one.
- Does Haiku 4.5's answer quality hold up on real student questions? Re-evaluate against
  `claude-sonnet-5` once Step 6 is running.

## References

- Models and pricing: <https://platform.claude.com/docs/en/about-claude/models/overview>
- Rate and spend limits: <https://platform.claude.com/docs/en/api/rate-limits>
- Streaming: <https://platform.claude.com/docs/en/build-with-claude/streaming>
- Prompt caching: <https://platform.claude.com/docs/en/build-with-claude/prompt-caching>
- Token counting (free): <https://platform.claude.com/docs/en/build-with-claude/token-counting>
- PHP SDK: <https://github.com/anthropics/anthropic-sdk-php>
