<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Clear stale jobs from queue
DB::table('jobs')->truncate();
DB::table('failed_jobs')->truncate();

// Reset all inbox docs and re-dispatch
$docs = App\Models\Document::inbox()->get();
foreach ($docs as $doc) {
    $doc->update(['ai_status' => 'pending', 'ai_error' => null]);
    App\Jobs\ProcessDocumentIntelligenceJob::dispatch($doc);
}
echo "Re-dispatched " . $docs->count() . " jobs" . PHP_EOL;
echo "Jobs in queue: " . DB::table('jobs')->count() . PHP_EOL;
