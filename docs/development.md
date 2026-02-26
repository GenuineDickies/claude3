# Roadside Assistance App — Development Documentation

## Architecture Overview

This project uses a hybrid architecture designed to work around the limitations of shared hosting while maintaining a robust local development environment.

### Stack
- **Framework:** Laravel 12 (PHP 8.2+)
- **Database:** MySQL
- **Frontend:** Blade + Tailwind CSS + Vite
- **Auth:** Laravel Breeze
- **SMS:** Telnyx PHP SDK v6
- **Geocoding:** Google Maps Geocoding API
- **Deploy target:** Shared hosting (cPanel)

### 1. The "Front Door" (Production Webhook Receiver)
Shared hosting often has strict file-count limits that make deploying a full Laravel application difficult. The production webhook receiver is a standalone, lightweight PHP script.

- **Location:** `deploy/lite-webhook-proxy/public_html/webhook-proxy/`
- **Files:** `webhook.php` (receives Telnyx webhooks), `locate.php` (captures GPS from customers), `config.php` (credentials)
- **Dependencies:** None — relies solely on PHP 8.2+ and the native `sodium` extension.
- **Configuration:** Uses `config.php` (generated from `config.example.php`) for `TELNYX_PUBLIC_KEY` and MySQL credentials.

### 2. The "Back Office" (Local Laravel Application)
The core application logic, database management, and dispatcher dashboard.

- **Location:** Root directory (`/var/www/html/claude3`)
- **Purpose:** Manage service requests, customers, estimates, SMS messaging, catalog, and settings.

---

## Data Model

```
Customer ──→ ServiceRequest ──→ Estimate ──→ EstimateItem
  │               │                              ↓ (optional)
  │               │                          CatalogItem ← CatalogCategory
  │               ├──→ Message
  │               ├──→ Vehicle (FK)
  │               └──→ ServiceType (FK)
  │
  ├──→ Vehicle
  └──→ Message

Setting           (key-value config store, cached, encryption support)
StateTaxRate      (US state → tax rate lookup)
MessageTemplate   (SMS templates with {{ variable }} placeholders)
User              (Breeze authentication)
```

### Models

| Model | Key Fields | Relationships |
|---|---|---|
| **Customer** | first_name, last_name, phone, sms_consent_at, sms_opt_out_at | serviceRequests, vehicles, messages |
| **ServiceRequest** | customer_id, vehicle_id, service_type_id, status, location, lat/lng, quoted_price, location_token | customer, vehicle, serviceType, messages, estimates |
| **Message** | service_request_id, customer_id, direction, body, telnyx_message_id, status | serviceRequest, customer |
| **MessageTemplate** | slug, name, body, category, variables | — |
| **Vehicle** | customer_id, year, make, model, color | customer, serviceRequests |
| **ServiceType** | name, default_price, is_active, sort_order | serviceRequests |
| **Estimate** | service_request_id, state_code, tax_rate, subtotal, tax_amount, total, status | serviceRequest, items |
| **EstimateItem** | estimate_id, catalog_item_id, name, unit_price, quantity, unit | estimate, catalogItem |
| **CatalogCategory** | name, type (part/service), is_active | items |
| **CatalogItem** | catalog_category_id, name, sku, unit_price, pricing_type (fixed/variable), unit | category |
| **Setting** | group, key, value, is_encrypted | — |
| **StateTaxRate** | state_code, state_name, tax_rate | — |
| **User** | name, email, password | — |

---

## Controllers

| Controller | Purpose |
|---|---|
| **ServiceRequestController** | CRUD for service requests (index, create, show, store) |
| **MessageController** | Send outbound SMS from service request detail (free-text or template) |
| **CustomerController** | AJAX customer search |
| **LocationShareController** | Request location via SMS, public GPS capture page, GPS submission |
| **EstimateController** | Estimates CRUD (create, store, show, edit, update, destroy) + tax rate lookup |
| **MessageTemplateController** | SMS templates CRUD + live preview |
| **CatalogController** | Categories and items CRUD |
| **SettingsController** | App settings editor + per-key updates |
| **StateTaxRateController** | US state tax rate management |
| **TelnyxWebhookController** | Single-action controller for inbound Telnyx webhooks |
| **ProfileController** | User profile (Breeze) |

---

## Routes

### Web (authenticated, `routes/web.php`)
| Method | URI | Purpose |
|---|---|---|
| GET | `/` | Redirect to dashboard or login |
| GET | `/dashboard` | Dashboard |
| GET | `/locate/{token}` | Public GPS share page (no auth) |
| GET/POST | `/service-requests` | List / create service requests |
| GET | `/service-requests/create` | New service request form |
| GET | `/service-requests/{sr}` | Service request detail |
| POST | `/service-requests/{sr}/request-location` | Send location-request SMS |
| POST | `/service-requests/{sr}/messages` | Send outbound message |
| Resource | `/message-templates` | Templates CRUD + preview |
| Various | `/catalog/**` | Categories + items CRUD |
| GET/PUT | `/settings` | Settings editor |
| GET/PUT | `/settings/tax-rates` | Tax rate management |
| PUT | `/settings/{key}` | Update single setting |
| Various | `/service-requests/{sr}/estimates/**` | Estimates CRUD |

### API (`routes/api.php`)
| Method | URI | Purpose |
|---|---|---|
| POST | `/api/locate/{token}` | GPS coordinates from browser (10/min throttle) |
| POST | `/webhooks/telnyx` | Telnyx inbound webhook (120/min throttle) |
| POST | `/webhook.php` | Backwards-compatible webhook route |

### Auth (`routes/auth.php`)
Standard Laravel Breeze: register, login, forgot-password, reset-password, verify-email, confirm-password, logout.

---

## Services

| Service | Description |
|---|---|
| **SmsServiceInterface** | Contract: `sendRaw(to, text)`, `sendTemplate(template, to, customer?, serviceRequest?, overrides?)` |
| **SmsService** | Telnyx SDK implementation, registered as singleton |

---

## Jobs

| Job | Description |
|---|---|
| **ReverseGeocodeJob** | Calls Google Maps Geocoding API with lat/lng, updates ServiceRequest `location` field |
| **SendSmsJob** | Sends template-based SMS via SmsService (3 retries, 15s backoff) |

---

## Events

| Event | When |
|---|---|
| **CustomerOptedIn** | Customer grants SMS consent |
| **CustomerOptedOut** | Customer revokes SMS consent |
| **LocationShared** | GPS coordinates received from customer |

---

## Console Commands

| Command | Signature | Description |
|---|---|---|
| **PruneExpiredLocationTokens** | `tokens:prune {--days=7}` | Clears expired location tokens from service_requests |

---

## Feature Status (as of Feb 24, 2026)

### Completed
- **Webhook receiver deployment** — Standalone `webhook.php` deployed to shared hosting with ED25519 signature verification.
- **Database schema** — 19 migrations covering all entities (customers, service requests, messages, vehicles, service types, estimates, catalog, settings, tax rates).
- **Telnyx webhook integration** — `TelnyxWebhookController` handles inbound messages with signature verification.
- **Customer management** — Customer model with SMS consent tracking (opt-in/opt-out with events).
- **Service request workflow** — Full CRUD with vehicle info, service type, quoted price, location tracking.
- **GPS location sharing** — Token-based location request via SMS → public GPS capture page → reverse geocoding.
- **Message templates** — CRUD with `{{ variable }}` placeholders, auto-resolution from Customer/ServiceRequest context, live preview.
- **Estimates** — Draft/sent/accepted/declined workflow with line items from catalog, US state tax calculation.
- **Parts & services catalog** — Categories (part/service types) + items with fixed/variable pricing.
- **Settings** — Key-value store with encryption support, cached, UI editor.
- **State tax rates** — All 50 US states + DC with management UI + seeder.
- **Authentication** — Laravel Breeze (login, register, password reset, email verification, profile management).
- **Outbound messaging** — Chat-style conversation thread on service request detail page with template picker and free-text compose. Auto-renders templates with context variables via AJAX.
- **Dispatcher dashboard** — Blade views for service requests, estimates, templates, catalog, and settings.
- **Test suite** — Feature tests for service request flow, location sharing, Telnyx webhooks, estimates, and auth.

### Pending / Future
- Service request status workflow automation (auto-advance status based on events).
- Reporting / analytics dashboard.
- Full production deployment of the Laravel app (currently local-only; only the lite webhook proxy is on the server).

---

## Environment Setup (Local)

1. Ensure PHP 8.2+, Composer, Node.js, and MySQL are installed.
2. Clone the repository.
3. `composer install`
4. `npm install`
5. Copy `.env.example` to `.env` and configure database credentials + Telnyx/Google Maps API keys.
6. `php artisan key:generate`
7. `php artisan migrate`
8. `php artisan db:seed` (populates service types, message templates, catalog items, tax rates)
9. `npm run build` (or `npm run dev` for HMR)
10. `php artisan serve --host=127.0.0.1 --port=8000`

**Quick start:** `composer setup` runs install + migrate + npm build in one command.

**Dev mode:** `composer dev` runs server + queue worker + log viewer + Vite concurrently.

---

## Telnyx Integration Details

- **Webhook URL (Production):** `https://YOURDOMAIN.com/webhook-proxy/webhook.php`
- **Webhook URL (Local):** `POST /webhooks/telnyx` (in `routes/api.php`, no CSRF)
- **Webhook API Version:** API v2
- **Signature Verification:** ED25519 detached signatures via `telnyx-signature-ed25519` and `telnyx-timestamp` headers.
- **SDK:** `telnyx/telnyx-php` v6

### Environment Variables
| Variable | Purpose |
|---|---|
| `TELNYX_API_KEY` | API authentication |
| `TELNYX_PUBLIC_KEY` | ED25519 webhook signature verification |
| `TELNYX_FROM_NUMBER` | Outbound SMS sender number |
| `TELNYX_MESSAGING_PROFILE_ID` | Messaging profile for outbound SMS |
| `GOOGLE_MAPS_API_KEY` | Reverse geocoding for GPS coordinates |

---

## Testing

Run the full test suite:
```bash
php artisan test
```

Or via Composer (clears config cache first):
```bash
composer test
```

### Test Coverage
| Test File | Coverage |
|---|---|
| `tests/Feature/ServiceRequestFlowTest` | Service request creation flow |
| `tests/Feature/LocationShareTest` | Location sharing workflow |
| `tests/Feature/TelnyxWebhookTest` | Inbound Telnyx webhook handling |
| `tests/Feature/MessageSendTest` | Outbound messaging from SR detail |
| `tests/Feature/EstimateTest` | Estimate CRUD operations |
| `tests/Feature/ProfileTest` | Profile management |
| `tests/Feature/Auth/*` (6 files) | Authentication, registration, password flows |

---

## Deployment Scripts (`scripts/`)

| Script | Purpose |
|---|---|
| `deploy-auto.sh` | Full deploy wizard (SSH key gen + upload) |
| `deploy-wizard.sh` | Interactive deploy wizard |
| `ssh-wizard.sh` | SSH key setup helper |
| `build-webhook-proxy-lite.sh` | Build 2-file lite proxy |
| `build-webhook-proxy-zip.sh` | Build proxy ZIP for upload |
| `build-webhook-proxy-public-root-*.sh` | Build full public-root archives |
| `upload-webhook-proxy-zip.sh` | Upload ZIP to hosting |

### Debug / Admin Scripts
| Script | Purpose |
|---|---|
| `debug-sms.php`, `debug-sms2.php` | Test SMS sending |
| `debug-location.php` | Test location features |
| `check-sr.php` | Inspect service request data |
| `check-telnyx-status.php` | Verify Telnyx API connectivity |
| `compliance-report.php` | SMS compliance audit |
| `restore-setting.php` | Restore a setting value |
