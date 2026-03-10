# API Reference

Last audited: 2026-03-08

This reference covers the current public endpoints in `routes/api.php` plus authenticated AJAX-style endpoints implemented in `routes/web.php`.

## Public APIs

### POST /api/locate/{token}

Purpose:

- Accept GPS coordinates from the customer-facing location page.

Middleware and behavior:

- Throttled at `10` requests per minute.
- No session auth.
- Rejects invalid, expired, or already-used tokens with `422`.

Request body:

```json
{
  "latitude": 33.749,
  "longitude": -84.388,
  "accuracy": 15.0
}
```

Success response:

```json
{
  "ok": true,
  "message": "Location received. Thank you!"
}
```

Side effects:

- Stores latitude and longitude on the service request.
- Sets `location_shared_at`.
- Dispatches `LocationShared`.
- Attempts best-effort reverse geocoding via `ReverseGeocodeJob`.

### POST /webhooks/telnyx

Purpose:

- Receive inbound Telnyx webhook events.

Middleware and behavior:

- Throttled at `120` requests per minute.
- Verifies ED25519 signature using `TELNYX_PUBLIC_KEY` or the encrypted setting value.
- Returns `401` when signature verification fails.

Supported behavior currently implemented:

- Processes `message.received` events.
- Logs inbound messages.
- Handles `START`, `STOP`, and `HELP` keyword flows.
- Sends a default auto reply for non-keyword inbound texts when a matching customer exists.

### POST /webhook.php

Backwards-compatible alias for the same Telnyx webhook controller, intended for subdirectory or lite-proxy deployments.

## Authenticated Session APIs

These endpoints live in the protected web middleware stack, so they require a valid session and inherit page-access enforcement.

### GET /api/customers/search

Purpose:

- Search customers during dispatch workflows.

### GET /api/service-types

Purpose:

- Return active catalog items used as selectable service types in the UI.

### GET /api/state-tax-rate/{stateCode}

Purpose:

- Return the configured state tax rate for estimate calculations.

### GET /api/message-templates

Purpose:

- Return active, non-compliance message templates for the compose UI.

### POST /api/message-templates/render

Purpose:

- Render a template against a service request and customer context before sending.

Request body:

```json
{
  "template_id": 1,
  "service_request_id": 42
}
```

Success response:

```json
{
  "rendered": "Rendered SMS text"
}
```

## Public Token Pages

These are browser-facing routes rather than JSON APIs, but they are part of the externally reachable interface:

- `GET /locate/{token}`
- `GET /sign/{token}` and `POST /sign/{token}`
- `GET /change-orders/{token}` and `POST /change-orders/{token}`
- `GET /estimates/approve/{token}` and `POST /estimates/approve/{token}`

## Dev-Only Utilities

When the app is in `local` or `testing`, the following helper route exists:

- `GET /test-location`
- `POST /test-location`

This route is intended for validating SMS location flows without going through the full dispatcher workflow.