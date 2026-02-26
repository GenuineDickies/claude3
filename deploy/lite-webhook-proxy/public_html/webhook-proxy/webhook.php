<?php

declare(strict_types=1);

/**
 * Telnyx Webhook (Lite) — drop-in webhook.php for shared hosting.
 *
 * Why this exists:
 * - Laravel + vendor/ means thousands of files.
 * - Some shared hosts make that painful (file-count limits, no SSH, no unzip).
 *
 * This file implements the same signature scheme used by Telnyx official SDKs:
 * - Headers:
 *   - telnyx-signature-ed25519: base64(ED25519 detached signature)
 *   - telnyx-timestamp: unix epoch seconds
 * - Signed payload: "{timestamp}|{raw_body}"
 */

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if (!is_array($err)) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int) ($err['type'] ?? 0), $fatalTypes, true)) {
        return;
    }

    $line = sprintf(
        "[%s] type=%s file=%s line=%s message=%s\n",
        gmdate('c'),
        (string)($err['type'] ?? '-'),
        (string)($err['file'] ?? '-'),
        (string)($err['line'] ?? '-'),
        (string)($err['message'] ?? '-')
    );

    @file_put_contents(__DIR__ . '/webhook-fatal.log', $line, FILE_APPEND);
});

$VERSION = '2026-02-21a';

$config = load_config();
$publicKeyB64 = $config['TELNYX_PUBLIC_KEY'] ?? getenv('TELNYX_PUBLIC_KEY') ?: '';
$tolerance = (int)($config['TELNYX_WEBHOOK_TOLERANCE'] ?? getenv('TELNYX_WEBHOOK_TOLERANCE') ?: 300);
$logPayload = (bool)($config['TELNYX_WEBHOOK_LOG_PAYLOAD'] ?? false);

// Deployment sanity check (does not require Telnyx headers)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $health = $_GET['health'] ?? null;
    if ($health === '1' || $health === 1) {
        $configPath = __DIR__ . '/config.php';
        $configExamplePath = __DIR__ . '/config.example.php';

        $configExists = is_file($configPath);
        $configReadable = $configExists ? is_readable($configPath) : false;
        $configPerms = $configExists ? substr(sprintf('%o', @fileperms($configPath) ?: 0), -4) : null;

        $configExampleExists = is_file($configExamplePath);
        $configExamplePerms = $configExampleExists ? substr(sprintf('%o', @fileperms($configExamplePath) ?: 0), -4) : null;

        $glob = glob(__DIR__ . '/config*.php') ?: [];
        $globBase = array_map(static fn($p) => basename((string) $p), $glob);

        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo json_encode([
            'version' => $VERSION,
            'ok' => true,
            'time' => gmdate('c'),
            'php_version' => PHP_VERSION,
            'sodium_available' => function_exists('sodium_crypto_sign_verify_detached'),
            'dir' => __DIR__,
            'dir_real' => realpath(__DIR__) ?: null,
            'config_php_exists' => $configExists,
            'config_php_readable' => $configReadable,
            'config_php_perms' => $configPerms,
            'config_php_real' => $configExists ? (realpath($configPath) ?: $configPath) : null,
            'config_example_exists' => $configExampleExists,
            'config_example_perms' => $configExamplePerms,
            'config_glob' => $globBase,
            'public_key_set' => $publicKeyB64 !== '',
            'tolerance' => $tolerance,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n";
        exit;
    }

    http_response_code(405);
    echo "Method Not Allowed\n";
    exit;
}

if ($publicKeyB64 === '') {
    http_response_code(500);
    echo "Missing TELNYX_PUBLIC_KEY\n";
    exit;
}

if (!function_exists('sodium_crypto_sign_verify_detached')) {
    http_response_code(500);
    echo "PHP sodium extension is required\n";
    exit;
}

$payload = file_get_contents('php://input');
if ($payload === false) {
    http_response_code(400);
    echo "Bad Request\n";
    exit;
}

$headers = get_request_headers_lower();
$signatureB64 = $headers['telnyx-signature-ed25519'] ?? null;
$timestamp = $headers['telnyx-timestamp'] ?? null;

if (!$signatureB64 || !$timestamp) {
    http_response_code(401);
    echo "Unauthorized\n";
    exit;
}

// Prevent replay attacks (same idea as official SDK)
$webhookTime = (int)$timestamp;
$now = time();
if ($tolerance > 0 && abs($now - $webhookTime) > $tolerance) {
    http_response_code(401);
    echo "Unauthorized\n";
    exit;
}

$publicKey = base64_decode($publicKeyB64, true);
$signature = base64_decode($signatureB64, true);

if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
    http_response_code(401);
    echo "Unauthorized\n";
    exit;
}

if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
    http_response_code(401);
    echo "Unauthorized\n";
    exit;
}

$signedPayload = $timestamp . '|' . $payload;
$isValid = sodium_crypto_sign_verify_detached($signature, $signedPayload, $publicKey);

if (!$isValid) {
    http_response_code(401);
    echo "Unauthorized\n";
    exit;
}

// Verified — now parse minimally (don’t log PII by default)
$eventType = null;
$eventId = null;
try {
    $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    $eventType = $data['data']['event_type'] ?? $data['data']['eventType'] ?? null;
    $eventId = $data['data']['id'] ?? null;
} catch (Throwable $e) {
    // Signature was valid; still accept the webhook to avoid retries.
}

log_webhook($eventType, $eventId, $logPayload ? $payload : null);

http_response_code(200);
echo "ok\n";

function load_config(): array
{
    $path = __DIR__ . '/config.php';
    if (!is_file($path)) {
        return [];
    }

    $config = require $path;
    return is_array($config) ? $config : [];
}

function get_request_headers_lower(): array
{
    $out = [];

    if (function_exists('getallheaders')) {
        $raw = getallheaders();
        if (is_array($raw)) {
            foreach ($raw as $k => $v) {
                $out[strtolower((string)$k)] = is_array($v) ? implode(', ', $v) : (string)$v;
            }
        }
        return $out;
    }

    foreach ($_SERVER as $key => $value) {
        if (!is_string($key)) {
            continue;
        }
        if (str_starts_with($key, 'HTTP_')) {
            $name = strtolower(str_replace('_', '-', substr($key, 5)));
            $out[$name] = is_array($value) ? implode(', ', $value) : (string)$value;
        }
    }

    return $out;
}

function log_webhook(?string $eventType, ?string $eventId, ?string $payloadOrNull): void
{
    $line = sprintf(
        "[%s] event_type=%s id=%s ip=%s\n",
        gmdate('c'),
        $eventType ?? '-',
        $eventId ?? '-',
        $_SERVER['REMOTE_ADDR'] ?? '-'
    );

    $logPath = __DIR__ . '/telnyx-webhooks.log';
    @file_put_contents($logPath, $line, FILE_APPEND);

    if ($payloadOrNull !== null) {
        $payloadPath = __DIR__ . '/telnyx-webhooks-payloads.log';
        @file_put_contents($payloadPath, "[$eventType] $payloadOrNull\n", FILE_APPEND);
    }
}
