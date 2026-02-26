<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sr = App\Models\ServiceRequest::latest()->first();

if (! $sr) {
    echo "No service requests found.\n";
    exit;
}

echo "SR id: {$sr->id}\n";
echo "Token: " . ($sr->location_token ?? 'null') . "\n";

if ($sr->location_token) {
    echo "URL: " . $sr->locationShareUrl() . "\n";
    echo "Valid: " . ($sr->isLocationTokenValid() ? 'yes' : 'no') . "\n";
    echo "Shared at: " . ($sr->location_shared_at ?? 'null') . "\n";
    echo "Lat: " . ($sr->latitude ?? 'null') . "\n";
    echo "Lng: " . ($sr->longitude ?? 'null') . "\n";
}
