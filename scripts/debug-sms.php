<?php
/**
 * Temp debug script — check latest service requests and SMS activity.
 * Run: php scripts/debug-sms.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Models\Message;
use App\Models\Customer;

echo "=== Last 5 Service Requests ===\n";
$srs = ServiceRequest::with(['customer', 'messages'])->latest()->take(5)->get();

foreach ($srs as $sr) {
    $c = $sr->customer;
    echo sprintf(
        "SR #%d | %s %s | phone=%s | consent=%s | status=%s | token=%s | lat=%s | msgs=%d\n",
        $sr->id,
        $c->first_name ?? '-',
        $c->last_name ?? '-',
        $c->phone ?? '-',
        $c->hasSmsConsent() ? 'YES' : 'NO',
        $sr->status,
        $sr->location_token ?? 'none',
        $sr->latitude ?? 'null',
        $sr->messages->count(),
    );

    foreach ($sr->messages as $m) {
        echo sprintf("   [%s] %s | %s\n", $m->direction, $m->status, substr($m->body, 0, 100));
    }
}

echo "\n=== Last 10 Messages (all) ===\n";
$msgs = Message::with('customer')->latest()->take(10)->get();
foreach ($msgs as $m) {
    $phone = $m->customer->phone ?? '?';
    echo sprintf(
        "%s | [%s] %s to/from %s | %s\n",
        $m->created_at->format('m-d H:i'),
        $m->direction,
        $m->status,
        $phone,
        substr($m->body, 0, 80),
    );
}
