<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Recent outbound messages ===\n\n";
$msgs = App\Models\Message::where('direction', 'outbound')->latest()->take(5)->get();
foreach ($msgs as $m) {
    echo "ID:{$m->id} SR:{$m->service_request_id} Status:{$m->status} Created:{$m->created_at}\n";
    echo $m->body . "\n---\n";
}
