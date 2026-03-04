<?php

/**
 * Copy this file to config.php and fill in values.
 * DO NOT commit config.php (it contains secrets).
 */

return [
    // Company name displayed on the customer-facing locate page.
    'COMPANY_NAME' => 'My Business',

    // From Telnyx Mission Control → Webhooks → "Public key" (Base64)
    'TELNYX_PUBLIC_KEY' => 'PASTE_BASE64_PUBLIC_KEY_HERE',

    // Default 300 (5 minutes). Set 0 to disable.
    'TELNYX_WEBHOOK_TOLERANCE' => 300,

    // Debug only. If true, writes raw JSON to telnyx-webhooks-payloads.log
    // (May contain PII)
    'TELNYX_WEBHOOK_LOG_PAYLOAD' => false,

    // Google Maps Embed API key (shows map on locate page).
    // Get one at: https://console.cloud.google.com → APIs & Services → Credentials
    // Must have "Maps Embed API" enabled.
    // Leave empty to fall back to OpenStreetMap.
    'GOOGLE_MAPS_API_KEY' => '',

    // ── App Callback (recommended) ─────────────────────────────────
    // When set, locate.php will POST captured GPS data back to your
    // Laravel app's API so the operator dashboard updates immediately.
    // This works across any deployment topology (different servers,
    // local dev via tunnel, etc.).
    //
    // Set this to the public URL of your Laravel app, e.g.:
    //   'https://yourdomain.com'  or  'https://abc123.ngrok.io'
    // Leave empty to skip the callback.
    'APP_CALLBACK_URL' => '',

    // ── Database (MySQL) ──────────────────────────────────────────
    // When set, locate.php will update the service_requests table
    // directly so the operator's dashboard reflects the GPS data
    // in real time. Used as a fallback if APP_CALLBACK_URL is not
    // set or the callback fails.
    //
    // Copy these from your Laravel .env on the same hosting account.
    // Leave DB_HOST empty to skip database updates (flat-file only).
    'DB_HOST'     => '',
    'DB_PORT'     => '3306',
    'DB_DATABASE' => '',
    'DB_USERNAME' => '',
    'DB_PASSWORD' => '',
];
