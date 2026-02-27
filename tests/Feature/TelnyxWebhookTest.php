<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TelnyxWebhookTest extends TestCase
{
    use RefreshDatabase;

    /** ED25519 keypair — shared across all tests in this class. */
    private string $publicKeyBytes;
    private string $secretKeyBytes;

    protected function setUp(): void
    {
        parent::setUp();

        $keypair = sodium_crypto_sign_keypair();
        $this->publicKeyBytes = sodium_crypto_sign_publickey($keypair);
        $this->secretKeyBytes = sodium_crypto_sign_secretkey($keypair);

        config([
            'services.telnyx.public_key'        => base64_encode($this->publicKeyBytes),
            'services.telnyx.webhook_tolerance'  => 300,
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Build a signed webhook request and POST it.
     */
    private function postSignedWebhook(array $data, string $uri = '/webhooks/telnyx')
    {
        $payload   = json_encode($data, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = base64_encode(
            sodium_crypto_sign_detached($timestamp . '|' . $payload, $this->secretKeyBytes),
        );

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE'                   => 'application/json',
            'HTTP_TELNYX_SIGNATURE_ED25519'   => $signature,
            'HTTP_TELNYX_TIMESTAMP'           => $timestamp,
        ], $payload);
    }

    /**
     * Build a typical Telnyx message.received payload.
     */
    private function inboundPayload(string $from, string $body, ?string $msgId = null): array
    {
        return [
            'data' => [
                'event_type' => 'message.received',
                'payload'    => [
                    'id'   => $msgId ?? 'telnyx-msg-' . uniqid(),
                    'from' => ['phone_number' => $from],
                    'text' => $body,
                ],
            ],
        ];
    }

    /**
     * Create an opted-in customer.
     */
    private function createOptedInCustomer(string $phone = '15551234567', string $firstName = 'Jane'): Customer
    {
        $customer = Customer::create([
            'first_name'     => $firstName,
            'last_name'      => 'Doe',
            'phone'          => $phone,
            'is_active'      => true,
            'sms_consent_at' => now(),
        ]);

        return $customer;
    }

    // ------------------------------------------------------------------
    // Signature verification
    // ------------------------------------------------------------------

    public function test_telnyx_webhook_accepts_valid_signature(): void
    {
        $response = $this->postSignedWebhook([
            'data' => ['event_type' => 'message.received'],
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    public function test_legacy_webhook_php_route_accepts_valid_signature(): void
    {
        $response = $this->postSignedWebhook(
            ['data' => ['event_type' => 'message.received']],
            '/webhook.php',
        );

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    public function test_telnyx_webhook_rejects_invalid_signature(): void
    {
        $payload   = json_encode(['data' => ['event_type' => 'message.received']], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();

        $response = $this->call('POST', '/webhooks/telnyx', [], [], [], [
            'CONTENT_TYPE'                   => 'application/json',
            'HTTP_TELNYX_SIGNATURE_ED25519'   => base64_encode(str_repeat("\x00", SODIUM_CRYPTO_SIGN_BYTES)),
            'HTTP_TELNYX_TIMESTAMP'           => $timestamp,
        ], $payload);

        $response->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // Inbound message logging
    // ------------------------------------------------------------------

    public function test_webhook_logs_inbound_message_from_known_customer(): void
    {
        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('logInbound')->once();

        $customer = $this->createOptedInCustomer();

        $response = $this->postSignedWebhook(
            $this->inboundPayload('+15551234567', 'I need help with my car', 'msg-abc-123'),
        );

        $response->assertOk();
        // Message + Correspondence creation now happens inside SmsService::logInbound,
        // verified by the mock expectation above (->once()).
    }

    public function test_webhook_does_not_crash_for_unknown_number(): void
    {
        $response = $this->postSignedWebhook(
            $this->inboundPayload('+19999999999', 'Hello'),
        );

        $response->assertOk();
        $this->assertDatabaseCount('messages', 0);
    }

    // ------------------------------------------------------------------
    // Compliance keywords — START / STOP / HELP
    // ------------------------------------------------------------------

    public function test_webhook_handles_start_keyword_and_grants_consent(): void
    {
        MessageTemplate::create([
            'slug'     => 'keyword-opt-in',
            'name'     => 'Opt-In Confirmation',
            'category' => 'compliance',
            'body'     => 'Thanks for subscribing!',
        ]);

        $customer = Customer::create([
            'first_name' => 'Bob',
            'last_name'  => 'Smith',
            'phone'      => '15551234567',
            'is_active'  => true,
        ]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($template) => $template->slug === 'keyword-opt-in')
            ->andReturn(['success' => true, 'message_id' => null, 'rendered_text' => '', 'error' => null]);
        $smsMock->shouldReceive('logInbound')->once();

        $response = $this->postSignedWebhook(
            $this->inboundPayload('+15551234567', 'START'),
        );

        $response->assertOk();
        $customer->refresh();
        $this->assertNotNull($customer->sms_consent_at);
        $this->assertTrue($customer->hasSmsConsent());
    }

    public function test_webhook_handles_stop_keyword_and_revokes_consent(): void
    {
        MessageTemplate::create([
            'slug'     => 'keyword-opt-out',
            'name'     => 'Opt-Out Confirmation',
            'category' => 'compliance',
            'body'     => 'You are unsubscribed.',
        ]);

        $customer = $this->createOptedInCustomer();
        // Ensure consent timestamp is in the past so STOP's now() is strictly after
        $customer->update(['sms_consent_at' => now()->subMinute()]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($template) => $template->slug === 'keyword-opt-out')
            ->andReturn(['success' => true, 'message_id' => null, 'rendered_text' => '', 'error' => null]);
        $smsMock->shouldReceive('logInbound')->once();

        $response = $this->postSignedWebhook(
            $this->inboundPayload('+15551234567', 'STOP'),
        );

        $response->assertOk();
        $customer->refresh();
        $this->assertFalse($customer->hasSmsConsent());
    }

    public function test_webhook_handles_help_keyword(): void
    {
        MessageTemplate::create([
            'slug'     => 'keyword-help',
            'name'     => 'Help Response',
            'category' => 'compliance',
            'body'     => 'Please call us for help.',
        ]);

        $customer = $this->createOptedInCustomer();

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($template) => $template->slug === 'keyword-help')
            ->andReturn(['success' => true, 'message_id' => null, 'rendered_text' => '', 'error' => null]);
        $smsMock->shouldReceive('logInbound')->once();

        $response = $this->postSignedWebhook(
            $this->inboundPayload('+15551234567', 'HELP'),
        );

        $response->assertOk();
    }

    public function test_webhook_keyword_matching_is_case_insensitive(): void
    {
        MessageTemplate::create([
            'slug'     => 'keyword-opt-in',
            'name'     => 'Opt-In',
            'category' => 'compliance',
            'body'     => 'Thanks!',
        ]);

        Customer::create([
            'first_name' => 'Case',
            'last_name'  => 'Test',
            'phone'      => '15551234567',
            'is_active'  => true,
        ]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn(['success' => true, 'message_id' => null, 'rendered_text' => '', 'error' => null]);
        $smsMock->shouldReceive('logInbound')->once();

        $response = $this->postSignedWebhook(
            $this->inboundPayload('+15551234567', 'start'),
        );

        $response->assertOk();
    }

    // ------------------------------------------------------------------
    // Auto-reply for non-keyword messages
    // ------------------------------------------------------------------

    public function test_webhook_sends_auto_reply_for_non_keyword_from_opted_in_customer(): void
    {
        MessageTemplate::create([
            'slug'     => 'inbound-auto-reply',
            'name'     => 'Auto Reply',
            'category' => 'general',
            'body'     => 'Thank you for your message!',
        ]);

        $customer = $this->createOptedInCustomer();

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($template) => $template->slug === 'inbound-auto-reply')
            ->andReturn(['success' => true, 'message_id' => null, 'rendered_text' => '', 'error' => null]);
        $smsMock->shouldReceive('logInbound')->once();

        $response = $this->postSignedWebhook(
            $this->inboundPayload('+15551234567', 'Where is my technician?'),
        );

        $response->assertOk();
    }

    public function test_webhook_skips_auto_reply_when_customer_has_no_consent(): void
    {
        MessageTemplate::create([
            'slug'     => 'inbound-auto-reply',
            'name'     => 'Auto Reply',
            'category' => 'general',
            'body'     => 'Thank you!',
        ]);

        Customer::create([
            'first_name' => 'No',
            'last_name'  => 'Consent',
            'phone'      => '15551234567',
            'is_active'  => true,
        ]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldNotReceive('sendTemplate');
        $smsMock->shouldReceive('logInbound')->once();

        $response = $this->postSignedWebhook(
            $this->inboundPayload('+15551234567', 'Hello?'),
        );

        $response->assertOk();
    }

    public function test_webhook_returns_event_type_in_response(): void
    {
        $response = $this->postSignedWebhook([
            'data' => ['event_type' => 'message.sent'],
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true, 'event_type' => 'message.sent']);
    }
}
