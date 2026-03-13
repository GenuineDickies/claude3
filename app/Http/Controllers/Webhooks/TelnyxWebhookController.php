<?php

namespace App\Http\Controllers\Webhooks;

use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Services\SmsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telnyx\Client;
use Telnyx\Core\Exceptions\WebhookVerificationException;

final class TelnyxWebhookController
{
    /** Keywords we respond to (case-insensitive). */
    private const KEYWORD_SLUGS = [
        'START' => 'keyword-opt-in',
        'STOP'  => 'keyword-opt-out',
        'HELP'  => 'keyword-help',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        $publicKey = Setting::getValue('telnyx_public_key', (string) config('services.telnyx.public_key', ''));

        if ($publicKey === '') {
            return response()->json(['ok' => false], 500);
        }

        $client = new Client(
            apiKey: Setting::getValue('telnyx_api_key', (string) config('services.telnyx.api_key', '')),
            publicKey: $publicKey,
        );

        $headers = $request->headers->all();
        $toleranceSeconds = (int) config('services.telnyx.webhook_tolerance', 300);

        try {
            $client->webhooks->verify($payload, $headers, tolerance: $toleranceSeconds);
        } catch (WebhookVerificationException $e) {
            Log::warning('Telnyx webhook signature verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'timestamp_header' => $headers['telnyx-timestamp'] ?? null,
                'signature_present' => isset($headers['telnyx-signature-ed25519']),
            ]);
            return response()->json(['ok' => false], 401);
        }

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json(['ok' => false], 400);
        }

        $eventType = $data['data']['event_type'] ?? null;

        // Handle inbound SMS
        if ($eventType === 'message.received') {
            $this->handleInboundMessage($data['data']['payload'] ?? []);
        }

        return response()->json(['ok' => true, 'event_type' => $eventType]);
    }

    /**
     * Process an inbound SMS — handle compliance keywords and log the message.
     */
    private function handleInboundMessage(array $payload): void
    {
        $from = $payload['from']['phone_number'] ?? null;
        $body = trim($payload['text'] ?? '');
        $telnyxMessageId = $payload['id'] ?? null;

        if (! $from) {
            return;
        }

        // Find the active customer by phone, including legacy formatted rows.
        $customer = Customer::findActiveByPhone($from);

        // Log inbound message
        $serviceRequestId = $customer?->serviceRequests()->latest()->value('id');
        $serviceRequest = $serviceRequestId ? \App\Models\ServiceRequest::find($serviceRequestId) : null;

        if ($customer) {
            app(SmsServiceInterface::class)->logInbound(
                customer: $customer,
                body: $body,
                telnyxMessageId: $telnyxMessageId,
                serviceRequest: $serviceRequest,
            );
        } else {
            // Log unknown sender for audit trail
            Log::info('Inbound SMS from unknown number', [
                'from' => $from,
                'body_preview' => substr($body, 0, 50),
                'telnyx_message_id' => $telnyxMessageId,
            ]);
        }

        // Check for compliance keywords
        $keyword = strtoupper(trim($body));

        if (! isset(self::KEYWORD_SLUGS[$keyword])) {
            // Not a keyword — send auto-reply if customer exists
            // Note: 'inbound-auto-reply' is a compliance template that bypasses consent checks
            if ($customer) {
                $autoReply = MessageTemplate::where('slug', 'inbound-auto-reply')->first();
                if ($autoReply) {
                    app(SmsServiceInterface::class)->sendTemplate(
                        template: $autoReply,
                        to: $from,
                        customer: $customer,
                    );
                }
            }

            return;
        }

        $slug = self::KEYWORD_SLUGS[$keyword];

        // Update consent based on keyword
        if ($customer) {
            match ($keyword) {
                'START' => $customer->grantSmsConsent([
                    'source' => 'sms_keyword',
                    'keyword' => 'START',
                    'phone' => $from,
                    'telnyx_message_id' => $telnyxMessageId,
                ]),
                'STOP'  => $customer->revokeSmsConsent([
                    'source' => 'sms_keyword',
                    'keyword' => 'STOP',
                    'phone' => $from,
                    'telnyx_message_id' => $telnyxMessageId,
                ]),
                default => null,
            };
        }

        // Send the compliance response
        $template = MessageTemplate::where('slug', $slug)->first();

        if (! $template) {
            Log::warning("Compliance template '{$slug}' not found in database.");
            return;
        }

        // Compliance messages bypass the consent gate in SmsServiceInterface
        app(SmsServiceInterface::class)->sendTemplate(
            template: $template,
            to: $from,
            customer: $customer,
        );
    }
}
