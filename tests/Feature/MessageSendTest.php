<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MessageSendTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function createCustomerWithRequest(bool $optedIn = true): array
    {
        $customer = Customer::create([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'phone'          => '5551234567',
            'is_active'      => true,
            'sms_consent_at' => $optedIn ? now() : null,
        ]);

        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);

        return [$customer, $sr];
    }

    // ------------------------------------------------------------------
    // Tests
    // ------------------------------------------------------------------

    public function test_send_free_text_message(): void
    {
        $mock = $this->mock(SmsServiceInterface::class);
        $mock->shouldReceive('sendRawWithLog')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg_abc', 'error' => null]);

        [$customer, $sr] = $this->createCustomerWithRequest();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.messages.store', $sr), [
                'body' => 'We are on our way!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_send_template_message(): void
    {
        $template = MessageTemplate::create([
            'slug'     => 'test-dispatch',
            'name'     => 'Dispatch Notification',
            'body'     => 'Hi {{ customer_first_name }}, help is on the way!',
            'category' => 'dispatch',
            'is_active' => true,
        ]);

        $mock = $this->mock(SmsServiceInterface::class);
        $mock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg_def', 'rendered_text' => 'Hi Jane, help is on the way!', 'error' => null]);

        [$customer, $sr] = $this->createCustomerWithRequest();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.messages.store', $sr), [
                'body'        => 'Hi Jane, help is on the way!',
                'template_id' => $template->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Template message sent.');
    }

    public function test_send_blocked_without_consent(): void
    {
        [$customer, $sr] = $this->createCustomerWithRequest(optedIn: false);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.messages.store', $sr), [
                'body' => 'Hello!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('messages', ['service_request_id' => $sr->id]);
    }

    public function test_send_requires_body(): void
    {
        [$customer, $sr] = $this->createCustomerWithRequest();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.messages.store', $sr), [
                'body' => '',
            ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_send_fails_gracefully(): void
    {
        $mock = $this->mock(SmsServiceInterface::class);
        $mock->shouldReceive('sendRawWithLog')
            ->once()
            ->andReturn(['success' => false, 'message_id' => null, 'error' => 'API error']);

        [$customer, $sr] = $this->createCustomerWithRequest();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.messages.store', $sr), [
                'body' => 'Test message',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_show_page_displays_compose_form(): void
    {
        [$customer, $sr] = $this->createCustomerWithRequest();

        $response = $this->actingAs(User::factory()->create())
            ->get(route('service-requests.show', $sr));

        $response->assertOk();
        $response->assertSee('Type a message');
        $response->assertSee('Send');
    }

    public function test_show_page_hides_compose_when_no_consent(): void
    {
        [$customer, $sr] = $this->createCustomerWithRequest(optedIn: false);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('service-requests.show', $sr));

        $response->assertOk();
        $response->assertSee('has not opted in to SMS');
        $response->assertDontSee('Type a message');
    }

    public function test_messages_display_in_thread(): void
    {
        [$customer, $sr] = $this->createCustomerWithRequest();

        Message::create([
            'service_request_id' => $sr->id,
            'customer_id'        => $customer->id,
            'direction'          => 'inbound',
            'body'               => 'I need help!',
            'status'             => 'received',
        ]);

        Message::create([
            'service_request_id' => $sr->id,
            'customer_id'        => $customer->id,
            'direction'          => 'outbound',
            'body'               => 'Help is on the way.',
            'status'             => 'sent',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('service-requests.show', $sr));

        $response->assertOk();
        $response->assertSee('I need help!');
        $response->assertSee('Help is on the way.');
    }

    public function test_template_render_api(): void
    {
        $template = MessageTemplate::create([
            'slug'     => 'test-render',
            'name'     => 'Test Render',
            'body'     => 'Hi {{ customer_first_name }}!',
            'category' => 'general',
            'is_active' => true,
        ]);

        [$customer, $sr] = $this->createCustomerWithRequest();

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('api.message-templates.render'), [
                'template_id'        => $template->id,
                'service_request_id' => $sr->id,
            ]);

        $response->assertOk();
        $response->assertJson(['rendered' => 'Hi Jane!']);
    }

    public function test_requires_authentication(): void
    {
        [$customer, $sr] = $this->createCustomerWithRequest();

        $response = $this->post(route('service-requests.messages.store', $sr), [
            'body' => 'Hello!',
        ]);

        $response->assertRedirect(route('login'));
    }
}
