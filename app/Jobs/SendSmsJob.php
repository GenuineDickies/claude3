<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Services\SmsServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send a template-based SMS via the queue.
 *
 * Use this for messages where the caller does not need instant
 * success/failure feedback (e.g. automated replies, scheduled
 * follow-ups).  For operator-initiated sends where the UI shows
 * "SMS sent" / "SMS failed", call SmsServiceInterface directly.
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 15;

    /**
     * @param  array<string, string>  $overrides  Variable overrides for template rendering
     */
    public function __construct(
        public MessageTemplate $template,
        public string $to,
        public ?Customer $customer = null,
        public ?ServiceRequest $serviceRequest = null,
        public array $overrides = [],
    ) {}

    public function handle(SmsServiceInterface $sms): void
    {
        $result = $sms->sendTemplate(
            template: $this->template,
            to: $this->to,
            customer: $this->customer,
            serviceRequest: $this->serviceRequest,
            overrides: $this->overrides,
        );

        if (! $result['success']) {
            Log::warning('SendSmsJob failed', [
                'template' => $this->template->slug,
                'to' => '***' . substr($this->to, -4),
                'error' => $result['error'] ?? 'unknown',
            ]);

            // Throw so the queue retries
            throw new \RuntimeException('SMS send failed: ' . ($result['error'] ?? 'unknown'));
        }
    }
}
