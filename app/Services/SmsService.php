<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Telnyx\Client;
use Telnyx\Core\Exceptions\APIException;

class SmsService implements SmsServiceInterface
{
    private Client $client;
    private string $fromNumber;
    private ?string $messagingProfileId;

    public function __construct()
    {
        $this->client = new Client(
            apiKey: Setting::getValue('telnyx_api_key', (string) config('services.telnyx.api_key', '')),
        );

        $this->fromNumber = Setting::getValue('telnyx_from_number', (string) config('services.telnyx.from_number', ''));
        $this->messagingProfileId = Setting::getValue('telnyx_messaging_profile_id', config('services.telnyx.messaging_profile_id')) ?: null;
    }

    /**
     * Send a raw SMS message to a phone number.
     *
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function sendRaw(string $to, string $text): array
    {
        $to = $this->formatE164($to);

        try {
            $params = [
                'to'   => $to,
                'from' => $this->fromNumber,
                'text' => $text,
            ];

            if ($this->messagingProfileId) {
                $params['messagingProfileID'] = $this->messagingProfileId;
            }

            $response = $this->client->messages->send(...$params);

            $messageId = $response->data?->id ?? null;

            return [
                'success'    => true,
                'message_id' => $messageId,
                'error'      => null,
            ];
        } catch (APIException $e) {
            Log::error('Telnyx SMS send failed', [
                'to'    => $to,
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);

            return [
                'success'    => false,
                'message_id' => null,
                'error'      => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a template-based SMS, auto-resolving variables from context.
     *
     * For non-compliance templates, the customer must have active SMS consent.
     * Compliance templates (opt-in, opt-out, help) are always allowed.
     *
     * @param  array<string, string>  $overrides  Manual variable overrides
     * @return array{success: bool, message_id: string|null, rendered_text: string, error: string|null}
     */
    public function sendTemplate(
        MessageTemplate $template,
        string $to,
        ?Customer $customer = null,
        ?ServiceRequest $serviceRequest = null,
        array $overrides = [],
    ): array {
        // Compliance templates are always sendable (opt-in, opt-out, help, welcome)
        $complianceSlugs = [
            'keyword-opt-in',
            'keyword-opt-out',
            'keyword-help',
            'welcome-message',
            'inbound-auto-reply',
        ];

        $isComplianceMessage = in_array($template->slug, $complianceSlugs, true);

        // Block non-compliance messages when customer hasn't opted in
        if (! $isComplianceMessage && $customer && ! $customer->hasSmsConsent()) {
            Log::warning('SMS blocked — customer has no active SMS consent', [
                'customer_id' => $customer->id,
                'template'    => $template->slug,
            ]);

            return [
                'success'       => false,
                'message_id'    => null,
                'rendered_text' => '',
                'error'         => 'Customer has not opted in to SMS. Send the welcome/opt-in message first.',
            ];
        }

        $renderedText = $template->renderWith($customer, $serviceRequest, $overrides);

        $result = $this->sendRaw($to, $renderedText);

        // Log the outbound message in the messages table if we have a customer
        if ($customer) {
            Message::create([
                'service_request_id' => $serviceRequest?->id,
                'customer_id'        => $customer->id,
                'direction'          => 'outbound',
                'body'               => $renderedText,
                'telnyx_message_id'  => $result['message_id'],
                'status'             => $result['success'] ? 'sent' : 'failed',
            ]);
        }

        return array_merge($result, ['rendered_text' => $renderedText]);
    }

    /**
     * Format a US phone number to E.164 (+1xxxxxxxxxx).
     */
    private function formatE164(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        }

        // Already includes country code or is non-US
        if (! str_starts_with($phone, '+')) {
            return '+' . $digits;
        }

        return $phone;
    }
}
