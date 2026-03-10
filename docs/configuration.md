# Configuration And Security Reference

Last audited: 2026-03-08

## 1. Local Environment

The current `.env.example` defines the active local baseline.

### Core application

| Variable | Purpose | Required |
|---|---|---|
| `APP_NAME` | UI and mail branding default | Yes |
| `APP_ENV` | Environment name | Yes |
| `APP_KEY` | Laravel encryption key | Yes |
| `APP_DEBUG` | Debug mode | Yes |
| `APP_URL` | Base URL for route generation | Yes |

### Database

| Variable | Purpose | Required |
|---|---|---|
| `DB_CONNECTION` | Database driver | Yes |
| `DB_HOST` | Database host | Yes |
| `DB_PORT` | Database port | Yes |
| `DB_DATABASE` | Application database | Yes |
| `DB_USERNAME` | Database username | Yes |
| `DB_PASSWORD` | Database password | Yes |

### Telnyx SMS and webhooks

| Variable | Purpose | Required |
|---|---|---|
| `TELNYX_API_KEY` | Outbound SMS API authentication | Yes for SMS |
| `TELNYX_FROM_NUMBER` | E.164 sender number | Yes for SMS |
| `TELNYX_MESSAGING_PROFILE_ID` | Messaging profile binding | Recommended in production |
| `TELNYX_PUBLIC_KEY` | ED25519 webhook verification key | Yes for webhooks |
| `TELNYX_WEBHOOK_TOLERANCE` | Signature tolerance window in seconds | Optional |

### Google Maps and location capture

| Variable | Purpose | Required |
|---|---|---|
| `GOOGLE_MAPS_API_KEY` | Embedded maps and reverse geocoding | Optional but needed for full location UX |
| `LOCATION_BASE_URL` | Alternate public location page URL | Optional |

### Document intelligence

| Variable | Purpose | Required |
|---|---|---|
| `DOCUMENT_AI_ENABLED` | Toggle AI processing | Optional |
| `DOCUMENT_AI_PROVIDER` | Current provider selector | Optional |
| `OPENAI_API_KEY` | API key for document intelligence | Required when enabled |
| `OPENAI_MODEL` | Model used for extraction/categorization | Optional |
| `DOCUMENT_AI_MAX_VISION_SIZE` | Vision-processing max size in bytes | Optional |

### Storage, sessions, queues, and PDFs

| Variable | Purpose | Required |
|---|---|---|
| `PDF_OUTPUT_DIR` | Custom PDF output path | Optional |
| `SESSION_DRIVER` | Defaults to `database` | Yes |
| `SESSION_LIFETIME` | Idle session timeout in minutes | Yes |
| `SESSION_ENCRYPT` | Encrypt session payloads | Optional |
| `QUEUE_CONNECTION` | Defaults to `database` | Yes |
| `CACHE_STORE` | Defaults to `database` | Yes |
| `FILESYSTEM_DISK` | Default storage disk | Yes |

## 2. In-App Settings Surface

The Settings model defines four groups in code:

- `General`
- `Google Maps`
- `Telnyx SMS`
- `Advanced`

Settings are cached and can be encrypted at rest. The following keys are currently encrypted when saved through the UI:

- `google_maps_api_key`
- `telnyx_api_key`

## 3. Queue And Job Behavior

- Default queue connection is `database`.
- `composer dev` runs `php artisan queue:listen` locally.
- Shared-hosting deployments should use scheduler or hosting-safe background execution instead of assuming a long-lived worker.
- Reverse geocoding is best-effort enrichment. It should never determine whether the customer’s GPS submission succeeds.

## 4. Session And Cookie Security

Verified defaults in `config/session.php`:

- `http_only=true`
- `same_site=lax`
- `driver=database`
- Cookie path `/`
- Optional secure-cookie behavior controlled by `SESSION_SECURE_COOKIE`

## 5. HTTP Security Headers

`SecurityHeaders` currently adds:

- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=(self)`
- `Strict-Transport-Security` on secure requests

## 6. Authentication And Password Handling

- Registration, login, password reset, and password confirmation are enabled through Breeze routes.
- Login and password-reset endpoints are rate-limited.
- Password creation and updates rely on Laravel password rules and hashing.
- Admin-created users also receive hashed passwords through `Hash::make()`.

## 7. CSRF And Public Exceptions

- Web forms use Laravel CSRF protection by default.
- Public webhook and location capture APIs live in `routes/api.php`, so they do not rely on CSRF cookies or session state.

## 8. Shared Hosting Notes

- Use the lite webhook proxy when exposing the full Laravel app is not feasible.
- Keep private application code and storage outside the public web root when deploying conventionally.
- Ensure `storage/` and `bootstrap/cache/` are writable.