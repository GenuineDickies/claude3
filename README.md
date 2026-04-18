# White Knight Roadside — CRM & Dispatch

Laravel 12 application powering roadside service dispatch, estimates, work orders, invoicing, payments, and accounting for WKR LLC.

## Stack

- **PHP 8.2+** / Laravel 12
- **MySQL** (production + staging) — sqlite OK for dev
- **Vite + Tailwind CSS 4** — dark "Dark Crystal" design system
- **Alpine.js** — sprinkled reactivity, no SPA framework
- **Blade** — server-rendered templates
- **Telnyx** — SMS + webhooks
- **Google Maps** — customer geocoding / locate links
- **OpenAI** — document AI (optional, toggle via `DOCUMENT_AI_ENABLED`)

## Layout

Standard Laravel layout — the `public/` directory is the web server document root. Everything else (app code, vendor, `.env`, storage) lives one level up and is not web-accessible.

On SiteGround and other shared hosts that default their document root to `public_html`, point the docroot at `<app-root>/public/` via cPanel → Domain → Change Document Root. A transition shim at `index.php` at the repo root forwards into `public/index.php` so the app still responds while docroot is being migrated; delete the shim once docroot is updated.

## Local setup

```bash
# 1. Clone
git clone <repo-url> wkrllc
cd wkrllc

# 2. Install PHP deps
composer install

# 3. Install JS deps
npm install

# 4. Copy env template and fill in secrets
cp .env.example .env
php artisan key:generate
#   then edit .env with your DB creds + Telnyx / Google / OpenAI keys

# 5. Run migrations
php artisan migrate

# 6. Compile assets
npm run build     # or: npm run dev  (for HMR)

# 7. Serve
php artisan serve
```

Open `http://localhost:8000`.

## Domain model — quick reference

Every transactional record traces back to a `ServiceRequest` (also called "service request" in the UI). The lifecycle chain:

```
Lead → ServiceRequest → Estimate → WorkOrder → Invoice → PaymentRecord → Receipt
                              ↑
                              └── ChangeOrder (on a WorkOrder)
```

Foreign keys are direct rather than chained where practical, so any child record can link back to its originating `ServiceRequest` with one FK lookup.

## Design system

Internal-tool dark theme — tokens defined in `resources/css/app.css`:

- `--color-accent` cyan (primary)
- `--color-violet` (secondary)
- `--color-accent-warm` amber (warnings / pending)
- `--color-success` green
- `--color-danger` red

Eight recurring UI patterns cover every view: `counter-strip`, `filter-chip-row`, `field-grid`, `form-section`, `sticky-actions`, `detail-header`, `workflow-track`, `right-rail`, `ledger-table`, `settings-layout`, `page-toolbar`, `table-crystal--dense`, `show-layout`, `auth-card`. Internal staff pages use `max-w-7xl`; customer-facing pages (auth, signature, approval, locate) stay narrow.

## Useful artisan commands

```bash
composer dev          # concurrent: serve + queue + pail logs + vite
php artisan test      # phpunit
php artisan pail      # live log tail
php artisan queue:work
```

## Deployment

Production runs on SiteGround. Deploy steps live in `DEPLOY.md` (not yet written — TODO).
