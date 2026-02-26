# Roadside Assistance App

Internal roadside-assistance dispatch application. Manages service requests, customer messaging via Telnyx SMS, GPS location sharing, estimates with tax calculation, and a parts/services catalog.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Database:** MySQL
- **Frontend:** Blade + Tailwind CSS + Vite
- **Auth:** Laravel Breeze
- **SMS:** Telnyx PHP SDK v6
- **Geocoding:** Google Maps API
- **Deploy:** Shared hosting (lite webhook proxy)

## Features

- **Service Requests** — Create and track roadside jobs with customer, vehicle, service type, and location info.
- **SMS Messaging** — Inbound/outbound messaging via Telnyx with opt-in/opt-out consent tracking.
- **GPS Location Sharing** — Send a link via SMS; customer taps to share GPS; reverse-geocoded to an address.
- **Message Templates** — Reusable SMS templates with `{{ variable }}` placeholders auto-resolved from context.
- **Estimates** — Line-item estimates from catalog with US state tax calculation (draft → sent → accepted/declined).
- **Parts & Services Catalog** — Categories and items with fixed/variable pricing.
- **Settings** — In-app configuration with encryption support for sensitive values.
- **Dispatcher Dashboard** — Full web UI for managing all of the above.

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database
php artisan migrate
php artisan db:seed

# Build frontend
npm run build

# Start dev server
php artisan serve --host=127.0.0.1 --port=8000
```

Or use the shortcut: `composer setup` (runs all of the above).

For development with HMR + queue worker + log viewer: `composer dev`

## Environment Variables

| Variable | Purpose |
|---|---|
| `DB_*` | MySQL connection |
| `TELNYX_API_KEY` | Telnyx API authentication |
| `TELNYX_PUBLIC_KEY` | ED25519 webhook signature verification |
| `TELNYX_FROM_NUMBER` | Outbound SMS sender |
| `TELNYX_MESSAGING_PROFILE_ID` | Messaging profile |
| `GOOGLE_MAPS_API_KEY` | Reverse geocoding |

## Testing

```bash
php artisan test
# or
composer test
```

## Documentation

See [docs/development.md](docs/development.md) for full architecture, data model, routes, and feature status.

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
