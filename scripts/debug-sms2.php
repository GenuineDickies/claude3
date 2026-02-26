<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$phone = $argv[1] ?? null;
if (!$phone) {
    echo "Usage: php scripts/debug-sms2.php <phone_number>\n";
    exit(1);
}

$customer = App\Models\Customer::where('phone', $phone)->first();
if (!$customer) {
    echo "Customer with phone {$phone} not found\n";
    exit(1);
}

echo "Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
echo "Phone: {$customer->phone}\n";
echo "SMS Consent: " . ($customer->hasSmsConsent() ? 'YES' : 'NO') . "\n";
echo "Consent granted at: " . ($customer->sms_consent_at ?? 'null') . "\n\n";

$msgs = App\Models\Message::where('customer_id', $customer->id)->latest()->get();
echo "=== Messages for this customer ===\n";
foreach ($msgs as $m) {
    echo sprintf(
        "%s | [%s] %s | telnyx_id=%s\n  body: %s\n\n",
        $m->created_at,
        $m->direction,
        $m->status,
        $m->telnyx_message_id ?? 'null',
        $m->body,
    );
}
