<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MessageTemplate;

$templates = MessageTemplate::orderBy('category')->orderBy('sort_order')->get();

echo str_repeat('=', 120) . PHP_EOL;
echo "10DLC COMPLIANCE REPORT" . PHP_EOL;
echo str_repeat('=', 120) . PHP_EOL . PHP_EOL;

$pass = 0;
$fail = 0;

foreach ($templates as $t) {
    $body = $t->body;
    $slug = $t->slug;

    // Check 1: Brand identification
    $hasBrand = str_contains($body, '{{ company_name }}');

    // Check 2: Frequency disclosure (required for opt-in/welcome/inbound)
    $hasFreq = (bool) preg_match('/frequency\s+may\s+vary|msg\s+freq/i', $body);

    // Check 3: Data rates disclosure
    $hasRates = (bool) preg_match('/data\s+rates\s+may\s+apply|msg.data\s+rates/i', $body);

    // Check 4: STOP instructions
    $hasStop = (bool) preg_match('/\bSTOP\b/', $body);

    // Check 5: HELP instructions
    $hasHelp = (bool) preg_match('/\bHELP\b/', $body);

    // Determine which checks are required for this template type
    $isCompliance = in_array($slug, ['keyword-opt-in', 'keyword-opt-out', 'keyword-help']);
    $isOptIn = in_array($slug, ['keyword-opt-in', 'welcome-message', 'inbound-auto-reply']);
    $isOptOut = $slug === 'keyword-opt-out';
    $isHelpResp = $slug === 'keyword-help';

    echo "[$t->category] $slug" . PHP_EOL;
    echo "  Body: " . substr($body, 0, 100) . (strlen($body) > 100 ? '...' : '') . PHP_EOL;

    // Brand: required for all
    $status = $hasBrand ? 'PASS' : 'FAIL';
    echo "  [1] Brand name:         $status" . PHP_EOL;
    $hasBrand ? $pass++ : $fail++;

    if ($isOptIn) {
        // Full opt-in checks
        $chks = [
            ['Frequency disclosure', $hasFreq],
            ['Data rates disclosure', $hasRates],
            ['STOP instructions',    $hasStop],
            ['HELP instructions',    $hasHelp],
        ];
        foreach ($chks as $i => [$label, $ok]) {
            $status = $ok ? 'PASS' : 'FAIL';
            $pad = max(0, 22 - strlen($label));
            echo "  [" . ($i + 2) . "] $label: " . str_repeat(' ', $pad) . "$status" . PHP_EOL;
            $ok ? $pass++ : $fail++;
        }

        // Extra for keyword-opt-in: consent not condition of purchase
        if ($slug === 'keyword-opt-in') {
            $hasConsent = (bool) preg_match('/consent\s+is\s+not\s+a\s+condition/i', $body);
            $status = $hasConsent ? 'PASS' : 'FAIL';
            echo "  [6] Consent disclaimer:  $status" . PHP_EOL;
            $hasConsent ? $pass++ : $fail++;
        }

        // Extra for inbound-auto-reply: privacy verbiage
        if ($slug === 'inbound-auto-reply') {
            $hasPrivacy = (bool) preg_match('/not\s+share|not\s+sell|will\s+not\s+be\s+sold/i', $body);
            $status = $hasPrivacy ? 'PASS' : 'FAIL';
            echo "  [6] Privacy verbiage:    $status" . PHP_EOL;
            $hasPrivacy ? $pass++ : $fail++;
        }
    } elseif ($isOptOut) {
        // STOP response: exact language check
        $hasUnsubMsg = (bool) preg_match('/unsubscribed.*no\s+further\s+messages/i', $body);
        $status = $hasUnsubMsg ? 'PASS' : 'FAIL';
        echo "  [2] Unsub confirmation:  $status" . PHP_EOL;
        $hasUnsubMsg ? $pass++ : $fail++;
    } elseif ($isHelpResp) {
        // HELP response: contact info
        $hasContact = str_contains($body, '{{ company_phone }}');
        $status = $hasContact ? 'PASS' : 'FAIL';
        echo "  [2] Contact info:        $status" . PHP_EOL;
        $hasContact ? $pass++ : $fail++;
    } else {
        echo "  [2] Operational msg:     OK (no further disclosure required)" . PHP_EOL;
        $pass++;
    }

    echo PHP_EOL;
}

echo str_repeat('=', 120) . PHP_EOL;
echo "RESULT: $pass checks PASSED, $fail checks FAILED" . PHP_EOL;
echo ($fail === 0 ? "ALL TEMPLATES 10DLC COMPLIANT" : "!! ACTION REQUIRED — FIX FAILING TEMPLATES !!") . PHP_EOL;
echo str_repeat('=', 120) . PHP_EOL;
