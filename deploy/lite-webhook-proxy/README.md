# Lite Telnyx webhook-proxy (shared hosting-friendly)

This is a **drop-in** replacement for the legacy endpoint:

- `https://YOURDOMAIN.com/webhook-proxy/webhook.php`

It verifies Telnyx webhooks using ED25519 signatures (same scheme used by Telnyx official SDKs), without Laravel or Composer.

## What changed vs “before”

The old setup was likely a small `webhook.php` script.

The Laravel app works, but it requires **thousands of files** (framework + `vendor/`), and shared hosting can make uploading/extracting that painful.

This “lite” option gets you back to uploading **2 files**.

## User action required

1) In your host's File Manager, create (or open) this folder:

- `/public_html/webhook-proxy/`

2) Upload these files into that folder:

- `webhook.php`
- `locate.php`
- `config.php` (create this by copying `config.example.php` locally and renaming it)

3) Edit `config.php` and set:

- `TELNYX_PUBLIC_KEY` to your Telnyx Webhook public key (Base64)
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` to your MySQL credentials (same as your Laravel `.env`). This lets `locate.php` update the `service_requests` table when a customer shares GPS.

4) Ensure the folder is writable for log files (usually default). The scripts write:

- `telnyx-webhooks.log`
- `location-captures.log`
- `captures/<token>.json` (per-capture structured data)

## How to verify it worked

1) First verify PHP is executing the file (no Telnyx required):

- Visit `https://YOURDOMAIN.com/webhook-proxy/webhook.php?health=1`

You should see JSON with `ok: true`.

2) In Telnyx Mission Control, trigger a real webhook event.

3) In File Manager, open:

- `telnyx-webhooks.log`

You should see a line like:

- `event_type=message.received ...`

## If you get HTTP 500

On shared hosting the most common cause is a **syntax error in** `config.php`.

1) Temporarily rename `config.php` to `config.php.bak` and retry:

- `https://YOURDOMAIN.com/webhook-proxy/webhook.php?health=1`

If the health check starts working again, your `config.php` has a typo.

2) Check this file in `/public_html/webhook-proxy/`:

- `webhook-fatal.log`

It contains the fatal error message and the line number that caused the 500.

## Notes

- If signature verification fails, the script returns HTTP `401`.
- If the PHP sodium extension is missing, it returns HTTP `500`.
- By default it does **not** log full payloads (to reduce PII exposure).
