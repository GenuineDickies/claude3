<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobs = DB::table('failed_jobs')->orderByDesc('id')->take(2)->get();
foreach ($jobs as $job) {
    echo "=== Job ID: {$job->id} ===" . PHP_EOL;
    echo "Failed at: {$job->failed_at}" . PHP_EOL;
    echo substr($job->exception, 0, 600) . PHP_EOL . PHP_EOL;
}

echo "Pending: " . DB::table('jobs')->count() . PHP_EOL;
echo "Failed: " . DB::table('failed_jobs')->count() . PHP_EOL;
