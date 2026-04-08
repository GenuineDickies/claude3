<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperationalFlowsE2ETest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    private function catalogItem(): CatalogItem
    {
        $category = CatalogCategory::first() ?? CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return CatalogItem::where('catalog_category_id', $category->id)->first()
            ?? CatalogItem::create([
                'catalog_category_id' => $category->id,
                'name' => 'Flat Tire Change',
                'base_cost' => 75.00,
                'unit' => 'each',
                'pricing_type' => 'fixed',
                'sort_order' => 1,
                'is_active' => true,
            ]);
    }

    private function intakePayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '(555) 999-0000',
            'customer_action' => 'create_new',
            'vehicle_year' => '2024',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
            'vehicle_color' => 'Silver',
            'catalog_item_id' => $this->catalogItem()->id,
            'quoted_price' => '75.00',
            'street_address' => '123 Main St',
            'city' => 'Tampa',
            'state' => 'FL',
            'notes' => 'Flat tire, driver side rear',
        ], $overrides);
    }

    private function createDispatchRequirements(ServiceRequest $serviceRequest): void
    {
        Estimate::create([
            'service_request_id' => $serviceRequest->id,
            'estimate_number' => 'EST-' . str_pad((string) (Estimate::count() + 1), 4, '0', STR_PAD_LEFT),
            'state_code' => 'WA',
            'tax_rate' => 0,
            'subtotal' => 250,
            'tax_amount' => 0,
            'total' => 250,
            'status' => 'accepted',
            'version' => 1,
            'is_locked' => false,
            'approved_at' => now(),
        ]);

        WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => 'WO-E2E-' . str_pad((string) (WorkOrder::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'assigned_to' => 'Tech One',
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
    }

    public function test_intake_with_verbal_consent_and_location_request_sends_sms_and_generates_token(): void
    {
        MessageTemplate::create([
            'slug' => 'location-request',
            'name' => 'Location Request',
            'body' => 'Share location {{ location_link }}',
            'category' => 'dispatch',
            'is_active' => true,
        ]);

        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn (MessageTemplate $template, string $to): bool => $template->slug === 'location-request' && $to === '5559990000')
            ->andReturn([
                'success' => true,
                'message_id' => 'msg-location',
                'rendered_text' => 'Share location',
                'error' => null,
            ]);

        $user = $this->user();

        $response = $this->actingAs($user)
            ->post(route('service-requests.store'), $this->intakePayload([
                'verbal_opt_in' => 1,
                'send_location_request' => 1,
            ]));

        $serviceRequest = ServiceRequest::query()->firstOrFail()->fresh('customer');

        $response->assertRedirect(route('service-requests.show', $serviceRequest));
        $response->assertSessionHas('success');
        $this->assertNotNull($serviceRequest->location_token);

        $customer = $serviceRequest->customer;
        $this->assertNotNull($customer?->sms_consent_at);
        $this->assertSame('verbal_intake', $customer?->sms_consent_meta['source'] ?? null);
        $this->assertSame($user->id, $customer?->sms_consent_meta['recorded_by_user_id'] ?? null);
    }

    public function test_intake_without_verbal_consent_blocks_location_request_sms(): void
    {
        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldNotReceive('sendTemplate');
        $sms->shouldNotReceive('sendRaw');

        $response = $this->actingAs($this->user())
            ->post(route('service-requests.store'), $this->intakePayload([
                'send_location_request' => 1,
            ]));

        $serviceRequest = ServiceRequest::query()->firstOrFail()->fresh('customer');

        $response->assertRedirect(route('service-requests.show', $serviceRequest));
        $response->assertSessionHas('warning');

        $this->assertNull($serviceRequest->location_token);
        $this->assertFalse((bool) $serviceRequest->customer?->hasSmsConsent());
    }

    public function test_dispatch_flow_moves_ticket_from_new_to_completed_with_logs(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('service-requests.store'), $this->intakePayload([
            'verbal_opt_in' => 1,
        ]));

        $serviceRequest = ServiceRequest::query()->firstOrFail();
        $this->createDispatchRequirements($serviceRequest);

        foreach (['dispatched', 'en_route', 'on_scene', 'completed'] as $status) {
            $this->actingAs($user)
                ->patch(route('service-requests.update', $serviceRequest), ['status' => $status])
                ->assertRedirect(route('service-requests.show', $serviceRequest));
        }

        $serviceRequest->refresh();
        $this->assertSame('completed', $serviceRequest->status);
        $this->assertDatabaseCount('service_request_status_logs', 4);
    }

    public function test_messaging_flow_after_verbal_consent_sends_dispatch_update(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('service-requests.store'), $this->intakePayload([
            'verbal_opt_in' => 1,
        ]));

        $serviceRequest = ServiceRequest::query()->firstOrFail()->fresh('customer');
        $customer = $serviceRequest->customer;
        $this->assertNotNull($customer);
        $this->assertTrue($customer->hasSmsConsent());

        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldReceive('sendRawWithLog')
            ->once()
            ->withArgs(function (string $to, string $text, Customer $calledCustomer, ServiceRequest $calledServiceRequest): bool {
                return $to === '5559990000'
                    && $text === 'Technician assigned and en route.'
                    && $calledCustomer->phone === '5559990000'
                    && $calledServiceRequest->id > 0;
            })
            ->andReturn([
                'success' => true,
                'message_id' => 'msg-dispatch',
                'error' => null,
            ]);

        $response = $this->actingAs($user)->post(route('service-requests.messages.store', $serviceRequest), [
            'body' => 'Technician assigned and en route.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Message sent.');
    }
}
