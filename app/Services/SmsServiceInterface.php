<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;

interface SmsServiceInterface
{
    /**
     * Send a raw SMS message.
     *
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function sendRaw(string $to, string $text): array;

    /**
     * Send a raw SMS and log both Message + Correspondence records.
     *
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function sendRawWithLog(
        string $to,
        string $text,
        Customer $customer,
        ?ServiceRequest $serviceRequest = null,
        string $subject = 'SMS',
        ?int $loggedBy = null,
    ): array;

    /**
     * Log an inbound SMS as both Message + Correspondence records.
     */
    public function logInbound(
        Customer $customer,
        string $body,
        ?string $telnyxMessageId = null,
        ?ServiceRequest $serviceRequest = null,
    ): void;

    /**
     * Send a template-based SMS, auto-resolving variables from context.
     *
     * @param  array<string, string>  $overrides
     * @return array{success: bool, message_id: string|null, rendered_text: string, error: string|null}
     */
    public function sendTemplate(
        MessageTemplate $template,
        string $to,
        ?Customer $customer = null,
        ?ServiceRequest $serviceRequest = null,
        array $overrides = [],
    ): array;
}
