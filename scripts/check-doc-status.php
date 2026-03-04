<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Queue State ===" . PHP_EOL;
echo "Pending jobs: " . DB::table('jobs')->count() . PHP_EOL;
echo "Failed jobs: " . DB::table('failed_jobs')->count() . PHP_EOL;
echo PHP_EOL;

echo "=== AI Config ===" . PHP_EOL;
echo "AI enabled: " . config('services.document_ai.enabled') . PHP_EOL;
echo "AI provider: " . config('services.document_ai.provider') . PHP_EOL;
echo "Model: " . config('services.document_ai.model') . PHP_EOL;
$key = config('services.document_ai.api_key');
echo "API key set: " . (!empty($key) ? "yes (" . substr($key, 0, 10) . "...)" : "NO") . PHP_EOL;
echo PHP_EOL;

echo "=== Inbox Documents ===" . PHP_EOL;
$docs = DB::table('documents')->whereNull('documentable_type')->get();
echo "Total: " . $docs->count() . PHP_EOL;

$statuses = [];
foreach ($docs as $d) {
    $statuses[$d->ai_status] = ($statuses[$d->ai_status] ?? 0) + 1;
}
foreach ($statuses as $s => $c) {
    echo "  {$s}: {$c}" . PHP_EOL;
}
