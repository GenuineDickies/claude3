<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Usage: php scripts/restore-setting.php [URL]
// Defaults to LOCATION_BASE_URL from .env if no argument given.
$url = $argv[1] ?? env('LOCATION_BASE_URL', '');
if (empty($url)) {
    echo "Error: No URL provided. Pass as argument or set LOCATION_BASE_URL in .env\n";
    exit(1);
}

App\Models\Setting::setValue('location_base_url', $url, false);

echo "Setting restored: " . App\Models\Setting::getValue('location_base_url', 'EMPTY') . PHP_EOL;

// Also show latest SR
$sr = App\Models\ServiceRequest::latest()->first();
if ($sr) {
    // Clear cache so the URL re-generates
    App\Models\Setting::clearCache();
    echo "SR id: {$sr->id}\n";
    echo "Token: " . ($sr->location_token ?? 'null') . "\n";
    if ($sr->location_token) {
        echo "URL: " . $sr->locationShareUrl() . "\n";
    }
}
