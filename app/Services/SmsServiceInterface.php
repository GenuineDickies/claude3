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
