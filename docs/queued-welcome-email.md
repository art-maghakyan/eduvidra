# Queued Welcome Email — Implementation Notes

## What was built

A queued welcome email for new student registrations, using Redis as the queue backend.

### Files added

| File | Purpose |
|---|---|
| `app/Mail/WelcomeEmail.php` | Mailable implementing `ShouldQueue`. Carries `$tries = 3` and exponential `$backoff = [10, 30, 60]` seconds. Uses `SerializesModels` so only the user's ID is stored in Redis, not the full model. |
| `resources/views/emails/welcome.blade.php` | Email template using Laravel's Markdown mail components (`<x-mail::message>`, `<x-mail::panel>`, `<x-mail::button>`). |
| `resources/views/vendor/mail/html/themes/eduvidra.css` | Custom indigo-palette theme for all Markdown emails. Published via `vendor:publish`, then customized. |
| `tests/Feature/Auth/WelcomeEmailTest.php` | Feature test covering: student + `just_registered` flag → email queued; no flag → no email; admin + flag → no email. |
| `tests/bootstrap.php` | Custom PHPUnit bootstrap that syncs `$_ENV` into `$_SERVER` after PHPUnit applies `<env>` overrides (see note below). |

### Files modified

| File | Change |
|---|---|
| `config/mail.php` | Added `'markdown' => ['theme' => 'eduvidra']` to activate the custom theme. |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Puts `'just_registered' => true` in the session before redirecting to 2FA setup. |
| `app/Http/Controllers/Auth/TwoFactorSetupController.php` | After `Auth::login()`, pulls the `just_registered` session flag and dispatches `WelcomeEmail` if it's a student. |
| `phpunit.xml` | Changed `bootstrap` to `tests/bootstrap.php`; added `force="true"` to all `<env>` entries. |
| `.env` | Changed `MAIL_MAILER` from `log` to `smtp`; added `MAIL_HOST=mailpit` and `MAIL_PORT=1025`. |
| `docker-compose.override.yml` | Added `mailpit` service (SMTP catcher, web UI on port 8025); added `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT` to the shared app environment; added `mailpit` to `x-app-depends-on`. |

### Why the dispatch lives in TwoFactorSetupController

All new users have `two_factor_required = true` by default (database migration default). This means every registration goes through the 2FA setup flow — the dashboard redirect in `RegisteredUserController` is never reached. The email fires in `TwoFactorSetupController::store()` instead, after the user has fully completed onboarding and the "Go to Dashboard" CTA in the email actually works.

The `just_registered` session flag distinguishes a brand-new student from an admin whose 2FA was reset by another admin (who would also pass through `TwoFactorSetupController` but should not get a welcome email).

### Why no Docker or queue config changes were needed (for queuing)

`QUEUE_CONNECTION=redis` was already set, the `redis` container was already running, and the `queue` container was already running `php artisan queue:work redis --sleep=1 --tries=3 --timeout=90`. The `jobs`, `job_batches`, and `failed_jobs` migration was also already in place.

### PHPUnit bootstrap fix (why tests/bootstrap.php exists)

The entire test suite was silently broken for all POST routes (every form returned 419) because of a Docker + PHPUnit + dotenv priority conflict:

1. The `app` container has `APP_ENV=local` in its environment, which PHP copies into both `$_ENV` and `$_SERVER`.
2. PHPUnit's `<env>` tags call `putenv()` and update `$_ENV` — but never touch `$_SERVER`.
3. Laravel's dotenv uses `ServerConstAdapter` as its **first** reader (checks `$_SERVER` before `$_ENV` or `getenv()`).
4. So `$_SERVER['APP_ENV']` = `local` won, `app()->environment()` returned `local`, and `runningUnitTests()` in the CSRF middleware returned `false` → 419 on every POST.

`tests/bootstrap.php` runs after PHPUnit has applied its overrides and syncs `$_ENV` into `$_SERVER`, fixing the priority inversion without touching Docker or CI config.

---

## Local dev — viewing emails

Emails in local dev are sent via **Gmail SMTP** using an App Password. Configure `.env` with:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD="xxxx xxxx xxxx xxxx"   # Google App Password — quote the value
MAIL_FROM_ADDRESS=your@gmail.com
```

After `docker compose up -d`, the `queue` container picks up dispatched jobs and delivers them to the configured Gmail address. Check the target inbox directly.

---

## Testing — automated (feature tests)

```bash
docker compose exec app php artisan test tests/Feature/Auth/WelcomeEmailTest.php
```

Uses `Mail::fake()` — no real email is sent. The three cases:
- Student + `just_registered` session flag → `WelcomeEmail` is queued for that user
- Student without the flag (e.g. admin reset 2FA) → nothing queued
- Admin + flag → nothing queued

---

## Testing — happy path (manual)

**1. Watch the queue worker:**
```bash
docker compose logs -f queue
```

**2. Register a new student** at `http://localhost:8000/register`, then complete the 2FA QR setup.

**3. Expected queue output:**
```
Processing: App\Mail\WelcomeEmail
Processed:  App\Mail\WelcomeEmail
```

**4. See the rendered email** in the inbox of `MAIL_USERNAME` (the Gmail account configured in `.env`).

---

## Testing — failure path

**Goal:** watch a job fail, retry with backoff, land in `failed_jobs`, then replay it.

**Step 1 — Make the job always fail.** Add this as the first line of `WelcomeEmail::handle()` in `app/Mail/WelcomeEmail.php`:
```php
throw new \RuntimeException('Simulated SMTP failure');
```

**Step 2 — Register a new student** and complete 2FA setup.

**Step 3 — Watch the queue log.** You'll see three attempts with 10s / 30s / 60s gaps between them, then the job moves to `failed_jobs`.

**Step 4 — Inspect failed jobs:**
```bash
docker compose exec app php artisan queue:failed
```

**Step 5 — Retry:**
```bash
# Replay a single job by UUID shown in the list above
docker compose exec app php artisan queue:retry <uuid>

# Or replay everything at once
docker compose exec app php artisan queue:retry all
```

**Step 6 — Remove the throw**, then retry again to confirm the job succeeds on replay.

**Step 7 — Clean up:**
```bash
# Delete one entry
docker compose exec app php artisan queue:forget <uuid>

# Or wipe the whole failed_jobs table
docker compose exec app php artisan queue:flush
```
