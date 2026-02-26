<?php
/**
 * Check Telnyx message delivery status via API.
 * Run: php scripts/check-telnyx-status.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Setting;

$apiKey = Setting::getValue('telnyx_api_key', config('services.telnyx.api_key', ''));
$fromNumber = Setting::getValue('telnyx_from_number', config('services.telnyx.from_number', ''));
$profileId = Setting::getValue('telnyx_messaging_profile_id', config('services.telnyx.messaging_profile_id', ''));

echo "=== Telnyx Config ===\n";
echo "API Key: " . substr($apiKey, 0, 12) . "...\n";
echo "From Number: {$fromNumber}\n";
echo "Messaging Profile ID: {$profileId}\n\n";

// Check message delivery status by ID
// Pass message IDs as arguments, or edit the defaults below
$messageIds = array_slice($argv, 1);
if (empty($messageIds)) {
    echo "Usage: php scripts/check-telnyx-status.php <message-id> [message-id...]\n";
    exit(1);
}

echo "=== Message Delivery Status ===\n";
foreach ($messageIds as $msgId) {
    $ch = curl_init("https://api.telnyx.com/v2/messages/{$msgId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json",
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "\nMessage ID: {$msgId}\n";
    echo "HTTP Status: {$httpCode}\n";

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $msg = $data['data'] ?? [];
        echo "To: " . ($msg['to'][0]['phone_number'] ?? '?') . "\n";
        echo "From: " . ($msg['from']['phone_number'] ?? '?') . "\n";
        echo "Status: " . ($msg['to'][0]['status'] ?? 'unknown') . "\n";
        echo "Direction: " . ($msg['direction'] ?? '?') . "\n";
        echo "Type: " . ($msg['type'] ?? '?') . "\n";
        echo "Created: " . ($msg['created_at'] ?? '?') . "\n";
        echo "Updated: " . ($msg['updated_at'] ?? '?') . "\n";

        // Show any errors
        if (!empty($msg['errors'])) {
            echo "ERRORS:\n";
            foreach ($msg['errors'] as $err) {
                echo "  Code: " . ($err['code'] ?? '?') . " - " . ($err['title'] ?? '') . ": " . ($err['detail'] ?? '') . "\n";
            }
        }
    } else {
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
}

// Also check the messaging profile to see webhook config
echo "\n\n=== Messaging Profile ===\n";
$ch = curl_init("https://api.telnyx.com/v2/messaging_profiles/{$profileId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json",
    ],
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true)['data'] ?? [];
    echo "Name: " . ($data['name'] ?? '?') . "\n";
    echo "Enabled: " . (($data['enabled'] ?? false) ? 'YES' : 'NO') . "\n";
    echo "Webhook URL: " . ($data['webhook_url'] ?? 'NOT SET') . "\n";
    echo "Webhook Failover: " . ($data['webhook_failover_url'] ?? 'NOT SET') . "\n";
    echo "Webhook API Version: " . ($data['webhook_api_version'] ?? '?') . "\n";
    echo "Number Pool: " . json_encode($data['number_pool_settings'] ?? null) . "\n";
    echo "Alpha Sender: " . ($data['alpha_sender_id'] ?? 'none') . "\n";
} else {
    echo "HTTP {$httpCode}: " . substr($response, 0, 500) . "\n";
}
