# White Knight Roadside Application

Last audited: 2026-03-08

This repository contains the internal White Knight Roadside operations platform. It combines dispatch, customer communication, GPS location capture, estimates, work orders, invoices, receipts, accounting, vendor documents, compliance tracking, and RBAC-controlled administration in one Laravel application.

## Stack

- Backend: Laravel 12 on PHP 8.2+
- Database: MySQL
- Frontend: Blade, Alpine-powered interactions, Tailwind CSS v4, Vite
- Authentication: Laravel Breeze with session auth
- Messaging: Telnyx PHP SDK v6
- Geocoding: Google Maps Geocoding API
- Document intelligence: OpenAI-backed document parsing when enabled
- Deployment target: shared hosting with optional standalone lite webhook proxy

## Core Modules

- Dispatch: service requests, rapid dispatch, status progression, customer lookup, vehicle details, service logs, photos, signatures, warranties, and evidence packages.
- Customer communication: outbound SMS, inbound webhook handling, compliance keywords, correspondence timeline, and reusable message templates.
- Customer-facing approvals: public location links, estimate approvals, signature capture, and change-order approvals.
- Commercial workflow: estimates, work orders, invoices, receipts, payment records, and change orders.
- Finance and operations: chart of accounts, journal entries, expenses, reports, vendor records, vendor documents, and API monitor endpoints.
- Administration: users, roles, page registry, page-to-role access assignment, settings, and audit logging.

## Quick Start

### Manual setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

### Composer shortcut

```bash
composer setup
```

`composer setup` installs PHP and Node dependencies, creates `.env` if needed, generates the app key, runs migrations, and builds frontend assets. It does not run seeders automatically.

### Full local dev loop

```bash
composer dev
```

`composer dev` starts the Laravel server, queue listener, log viewer, and Vite dev server together.

## Required Configuration

Minimum local configuration:

- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- `TELNYX_API_KEY`, `TELNYX_PUBLIC_KEY`, `TELNYX_FROM_NUMBER`
- `GOOGLE_MAPS_API_KEY` if you want reverse geocoding and embedded maps

Optional but important features:

- `TELNYX_MESSAGING_PROFILE_ID` for production messaging profile routing
- `LOCATION_BASE_URL` when customer location capture is hosted outside the main Laravel app
- `DOCUMENT_AI_ENABLED`, `DOCUMENT_AI_PROVIDER`, `OPENAI_API_KEY`, `OPENAI_MODEL` for document parsing
- `PDF_OUTPUT_DIR` if PDF generation should target a specific private storage path

See [docs/configuration.md](docs/configuration.md) for the full environment and settings reference.

## Security Defaults

- Passwords are hashed through Laravel's `hashed` cast and `Hash::make()` flows.
- Web routes use Laravel CSRF protection by default.
- Protected application routes run behind `auth`, `active-user`, and `page-access` middleware.
- Session cookies default to `http_only=true` and `same_site=lax`.
- `SecurityHeaders` adds `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, and HSTS on secure requests.
- Telnyx webhooks are ED25519-verified using the configured public key and tolerance.

## Testing

```bash
php artisan test
```

Or:

```bash
composer test
```

Documentation-backed testing rules:

- Feature tests must not depend on live third-party services.
- Queue or HTTP work that reaches Google Maps, Telnyx, or other providers should be faked unless the test explicitly covers that boundary.
- Reverse geocoding is best-effort enrichment after a successful location submission. Failures should be logged, not surfaced as a customer-facing 500.

## Documentation Map

- [docs/development.md](docs/development.md): documentation index and current module map
- [docs/architecture.md](docs/architecture.md): architecture overview, data flows, and Mermaid diagrams
- [docs/schema-reference.md](docs/schema-reference.md): grouped table reference derived from migrations and model relationships
- [docs/access-control.md](docs/access-control.md): RBAC and protected page registration
- [docs/admin-guide.md](docs/admin-guide.md): admin workflows and day-to-day management
- [docs/user-guide.md](docs/user-guide.md): dispatcher and operator workflows
- [docs/configuration.md](docs/configuration.md): environment variables, settings, and security setup
- [docs/api-reference.md](docs/api-reference.md): public and authenticated endpoints
- [docs/documentation-audit-report.md](docs/documentation-audit-report.md): structured audit results and changes made

## Deploying the Webhook Proxy

### Option C: "Lite" (2-file) webhook (recommended if your host blocks big uploads)

If you **cannot upload thousands of Laravel/vendor files**, use the drop-in script at:

- [deploy/lite-webhook-proxy/README.md](deploy/lite-webhook-proxy/README.md)

It restores the old-style deployment:

- Upload `webhook.php` + `config.php` into `/public_html/webhook-proxy/`
- Point Telnyx to `https://YOURDOMAIN.com/webhook-proxy/webhook.php`

This still verifies Telnyx ED25519 signatures (same scheme as Telnyx SDKs), but without Laravel.

This project is designed so your main website can stay at `/`, while Telnyx webhooks hit:

- `POST https://YOURDOMAIN.com/webhook-proxy/webhook.php`

### Recommended (upload 1 ZIP file)

1) Build a deployment zip locally (WSL):

- `bash scripts/build-webhook-proxy-zip.sh`

This creates:

- `storage/app/deploy/webhook-proxy.zip`

Optional: run the one-command wizard that will build the ZIP (if needed), set up SSH, and upload it:

- `bash scripts/deploy-wizard.sh`

If you are having trouble pasting private keys (or see `error in libcrypto`), use the safer helper that NEVER requires pasting a private key and instead generates a local RSA key and prints a one-line public key to import:

- `bash scripts/deploy-auto.sh`

2) Upload + extract on your host:

- Upload the ZIP to `public_html/webhook-proxy/`
- Use your host's File Manager to **Extract** it there

3) Put the rest of the app outside `public_html` (recommended):

- Create a private folder like `~/laravel/claude3/`
- Move everything except the `public_html/webhook-proxy/` files into `~/laravel/claude3/`
- Edit `public_html/webhook-proxy/index.php` so the `vendor/autoload.php` and `bootstrap/app.php` paths point to `~/laravel/claude3/`

4) Server install (requires SSH):

- `cd ~/laravel/claude3 && composer install --no-dev --optimize-autoloader`

5) Configure env on the server:

- Copy `.env.example` to `.env` on the server and fill required values
- Ensure `TELNYX_PUBLIC_KEY` is set (required for webhook verification)

---

### Fallback (no SSH / can't run composer)

If SSH key auth is blocked or Composer can't be run on the server, you can deploy a single archive that includes `vendor/` and is laid out so it can be extracted directly into `public_html/webhook-proxy/`.

1) Build the public-root archive locally (WSL):

- If your hosting supports `.zip`:
	- `bash scripts/build-webhook-proxy-public-root-zip.sh`
- If your hosting rejects `.zip`, use `.tar.gz`:
	- `bash scripts/build-webhook-proxy-public-root-targz.sh`

This creates one of:

- `storage/app/deploy/webhook-proxy-public-root.zip`
- `storage/app/deploy/webhook-proxy-public-root.tar.gz`

2) Upload + extract on your host:

- Upload the archive to `public_html/webhook-proxy/`
- Use File Manager → Extract

3) Create `.env` on the server (do NOT upload your local `.env`):

- Copy `.env.example` to `.env` and fill required values
- Set `APP_ENV=production`, `APP_DEBUG=false`
- Ensure `TELNYX_PUBLIC_KEY` is set

4) Permissions:

- In File Manager, ensure `storage/` and `bootstrap/cache/` are writable by PHP.

### Verify

- `https://YOURDOMAIN.com/webhook-proxy/up` should respond OK
- `curl -i -X POST https://YOURDOMAIN.com/webhook-proxy/webhook.php -H "Content-Type: application/json" -d '{}'` should return `401` (expected: unsigned)
