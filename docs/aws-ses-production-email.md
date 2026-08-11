# Production Email via AWS SES — Implementation Notes

## What was built

Production now sends transactional mail (starting with the queued [WelcomeEmail](queued-welcome-email.md)) through **AWS SES** in `eu-central-1`, authenticated by the EC2 instance's IAM role. Local dev is unchanged — it keeps using Gmail SMTP.

## How it works

Laravel's `ses` mail transport (registered in `config/mail.php`) reads its credentials from `config/services.php`:

```php
'ses' => [
    'key'    => env('AWS_ACCESS_KEY_ID'),     // empty in prod
    'secret' => env('AWS_SECRET_ACCESS_KEY'), // empty in prod
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
],
```

Because `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` are **left empty** in production, the AWS SDK falls back to its default credential provider chain and picks up the **EC2 instance-profile (IAM role)** credentials automatically. No long-lived secrets are stored on disk. This is the same auth model already used for S3 and CloudWatch.

`WelcomeEmail` has no hardcoded `from()`, so it uses the global `config('mail.from')`, driven by `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`. The from address must belong to an identity verified in SES.

### Files changed

| File | Change |
|---|---|
| `composer.json` / `composer.lock` | Added `aws/aws-sdk-php:^3.322.9` — required by Laravel's SES transport. |
| `.env.production.example` | `MAIL_MAILER=ses`, `MAIL_FROM_ADDRESS="noreply@eduvidra.com"`. AWS keys stay empty; `AWS_DEFAULT_REGION=eu-central-1`. |
| `.env.prod.local` | Updated `MAIL_FROM_ADDRESS`; kept `MAIL_MAILER=log` (the local prod-like stack has no IAM role — see note in the file). |

> No changes were needed in `config/mail.php` or `config/services.php` — the `ses` mailer and `services.ses` block already existed.

The real production `.env` on the EC2 box must receive the same `MAIL_MAILER` / `MAIL_FROM_ADDRESS` edits (operator step — that file is not in the repo).

## AWS-side setup (one time, manual)

These are prerequisites; SES will not deliver until they're done.

### 1. Verify the sending domain in SES (`eu-central-1`)

In the SES console (Frankfurt region), create a **domain identity** for `eduvidra.com` and enable Easy DKIM. Publish the records SES gives you in the `eduvidra.com` DNS zone:

- **3× DKIM CNAME** records (`*._domainkey.eduvidra.com`) — required; identity stays "pending" until these resolve.
- **Custom MAIL FROM** subdomain (e.g. `mail.eduvidra.com`) with its **MX** and **SPF TXT** (`v=spf1 include:amazonses.com ~all`) — improves deliverability and alignment.
- **DMARC TXT** at `_dmarc.eduvidra.com` (e.g. `v=DMARC1; p=none; rua=mailto:dmarc@eduvidra.com`) — start with `p=none` and tighten later.

### 2. Grant the EC2 instance IAM role permission to send

Attach this statement to the instance role (inline or managed policy):

```json
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Action": ["ses:SendEmail", "ses:SendRawEmail"],
    "Resource": "*"
  }]
}
```

Optionally scope `Resource` to the verified identity ARN and add a `ses:FromAddress` condition to restrict which addresses the role may send as.

### 3. Request production access (leave the SES sandbox)

New SES accounts are in **sandbox mode**: mail is only delivered to *verified* recipient addresses, with a low sending cap. Submit a "Request production access" in the SES console. Until it's granted, student registrations to arbitrary addresses will be **rejected** (`MessageRejected: Email address is not verified`).

## Verification

### Config sanity (local, no real send)

```bash
# SDK present and SES client class resolves
docker compose exec app php -r "require 'vendor/autoload.php'; echo class_exists('Aws\\Ses\\SesClient') ? 'SES SDK ok' : 'MISSING';"
```

### Production smoke test (after AWS setup + deploy)

1. **Deploy** via `bin/deploy.sh`. It runs `config:cache`, baking `MAIL_MAILER=ses` into the cached config. Confirm:
   ```bash
   php artisan tinker --execute="echo config('mail.default').' '.config('mail.from.address');"
   # expect:  ses  noreply@eduvidra.com
   ```
2. **Send a one-off** from the box and watch the worker:
   ```bash
   php artisan tinker --execute="Mail::raw('SES test', fn(\$m) => \$m->to('you@verified.example')->subject('SES smoke test'));"
   docker compose logs -f queue   # expect: Processed: ...
   ```
3. **End-to-end:** register a new student, complete 2FA setup, confirm the welcome email arrives from `noreply@eduvidra.com`. Check SES sending statistics / CloudWatch for bounces or complaints.

### Failure modes

| Symptom | Likely cause |
|---|---|
| `MessageRejected: Email address is not verified` | Still in the SES sandbox, or sending in the wrong region. |
| Signature / `Could not resolve host` / `403` errors | Region mismatch, or the IAM role is missing `ses:SendEmail` / `ses:SendRawEmail`. |
| Mail logged, never sent | `MAIL_MAILER` still `log` (config cache not refreshed after the `.env` change). |

## Bounce and complaint handling

### What was built

SNS webhook that suppresses email addresses on permanent bounces and spam complaints, preventing further sends to those addresses.

| File | Role |
|---|---|
| `app/Http/Controllers/SesWebhookController.php` | Handles `POST /webhooks/ses` — confirms SNS subscriptions, processes bounce/complaint notifications |
| `app/Models/EmailSuppression.php` | `email_suppressions` table model; `isSuppressed(string $email): bool` static helper |
| `app/Enums/SuppressionType.php` | `BOUNCE` / `COMPLAINT` enum |
| `database/migrations/2026_06_24_000001_create_email_suppressions_table.php` | `email` (unique), `type`, `subtype` (nullable), timestamps |
| `app/Http/Controllers/Auth/TwoFactorSetupController.php` | Checks `EmailSuppression::isSuppressed()` before queuing the welcome email |

Transient bounces (`bounceType != Permanent`) are ignored — only hard bounces are suppressed.

Address matching is case-insensitive throughout. SES reports bounced recipients using the casing of the original message, so a bounce for `John.Doe@Example.com` must still suppress a user registered as `john.doe@example.com`.

### Authenticating the webhook

The endpoint is public and CSRF-exempt — SNS cannot send a CSRF token or log in — so every delivery is authenticated twice before it can write to the suppression list:

1. **SNS signature verification** (`aws/aws-php-sns-message-validator`). The signing certificate is fetched from the `SigningCertURL` (itself constrained to a regional SNS host), and the message signature is verified against it. Forging this requires AWS's private key. Failures return `403`.
2. **Topic ARN allow-list** (`SES_WEBHOOK_TOPIC_ARN`). A valid signature only proves *some* AWS account sent the message — any AWS customer could point their own topic at this endpoint. The `TopicArn` must match the configured allow-list, which accepts a comma-separated list. **Unset means reject everything**, so this must be set wherever the webhook is reachable.

Without these, an unauthenticated `curl` could suppress arbitrary addresses — silently, since a suppressed address just stops receiving mail and logs it as normal behaviour.

`SubscribeURL` values are additionally checked to be `https` on a regional SNS host before the confirmation request is followed, so a hostile topic cannot use this endpoint to make the server fetch arbitrary URLs.

### AWS-side setup (one time, manual)

1. In SES → Identities → `eduvidra.com` → **Notifications** → click **Edit feedback notifications** or **Create SNS topic**:
   - Create SNS topic `ses-bounce-complaint` (Standard type)
   - Set **Bounce feedback** → `ses-bounce-complaint`
   - Set **Complaint feedback** → `ses-bounce-complaint`
   - Save changes

2. In SNS → Topics → `ses-bounce-complaint` → **Create subscription**:
   - Protocol: **HTTPS**
   - Endpoint: `https://eduvidra.com/webhooks/ses`
   - The controller auto-confirms the subscription by following the `SubscribeURL`
   - Subscription status should flip to **Confirmed** within seconds

The `/webhooks/ses` route is CSRF-exempt (configured via `preventRequestForgery(except: ['webhooks/ses'])` in `bootstrap/app.php`).

### Verification in production

**Check SNS subscription confirmed:**
In CloudWatch → Log groups → `/eduvidra/prod/app`, search for:
```
SNS subscription confirmed
```

**Test bounce flow:**
In SES console → Identities → `eduvidra.com` → **Send test email**:
- From-address: `noreply`
- Scenario: **Bounce**

Then check CloudWatch for:
```
Email suppressed due to hard bounce {"email": "bounce@simulator.amazonses.com", "subtype": "General"}
```

And verify the DB row:
```bash
docker compose exec app php artisan tinker --execute="App\Models\EmailSuppression::all()->toArray()"
```

**Test complaint flow:**
Same as above but Scenario: **Complaint**. Expect `Email suppressed due to complaint` in CloudWatch.

### Operations

**Remove an address from the suppression list.** Hard bounces are permanent by design, but addresses do get suppressed wrongly — a transient AWS issue reported as permanent, a recycled mailbox, a typo'd test send. There is no automatic expiry, so removal is manual:

```bash
docker compose exec app php artisan mail:unsuppress user@example.com
```

Exit code `0` on removal, `1` if the address was not suppressed. The lookup is case-insensitive, and the removal is logged (`Suppression removed via console`) so it appears in CloudWatch alongside the `Email suppressed due to …` entries.

### Failure modes

| Symptom | Likely cause |
|---|---|
| Subscription stays `Pending confirmation` | Webhook not deployed, wrong URL, or CSRF not exempted |
| No log entries after sending test email | SNS topic not wired to the SES identity, or wrong region |
| `Invalid payload` 400 in logs | Raw message delivery is enabled on the subscription — it strips the signature envelope and must be off |
| `Rejected SNS delivery: unrecognised topic` 403 | `SES_WEBHOOK_TOPIC_ARN` is unset, or set to the **subscription** ARN (the one with a trailing UUID) instead of the **topic** ARN |
| `Rejected SNS delivery: invalid signature` 403 | Raw message delivery enabled, or the payload did not originate from SNS |
| Bounces silently stop being recorded after a deploy | `SES_WEBHOOK_TOPIC_ARN` missing from the server `.env` — the check fails closed |

## Out of scope (possible follow-ups)

- Local dev mail stays on Gmail SMTP — unchanged.
- Queue mechanics are unchanged: `WelcomeEmail` still queues through Redis; SES is only the transport at delivery time.
