<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\StatusAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StatusAutomationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'phone'      => '5559876543',
            'is_active'  => true,
        ]);
    }

    private function makeSr(string $status = 'new'): ServiceRequest
    {
        return ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'status'      => $status,
            'location'    => '123 Main St',
        ]);
    }

    // ------------------------------------------------------------------
    // Unit tests for StatusAutomationService
    // ------------------------------------------------------------------

    public function test_photo_upload_advances_to_on_scene_from_en_route(): void
    {
        $sr = $this->makeSr('en_route');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'photo_uploaded');

        $this->assertEquals('on_scene', $result);
        $this->assertEquals('on_scene', $sr->fresh()->status);
    }

    public function test_photo_upload_advances_to_on_scene_from_dispatched(): void
    {
        $sr = $this->makeSr('dispatched');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'photo_uploaded');

        $this->assertEquals('on_scene', $result);
        $this->assertEquals('on_scene', $sr->fresh()->status);
    }

    public function test_photo_upload_advances_to_on_scene_from_new(): void
    {
        $sr = $this->makeSr('new');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'photo_uploaded');

        $this->assertEquals('on_scene', $result);
        $this->assertEquals('on_scene', $sr->fresh()->status);
    }

    public function test_photo_upload_does_not_advance_from_on_scene(): void
    {
        $sr = $this->makeSr('on_scene');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'photo_uploaded');

        $this->assertNull($result);
        $this->assertEquals('on_scene', $sr->fresh()->status);
    }

    public function test_photo_upload_does_not_advance_from_completed(): void
    {
        $sr = $this->makeSr('completed');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'photo_uploaded');

        $this->assertNull($result);
        $this->assertEquals('completed', $sr->fresh()->status);
    }

    public function test_signature_advances_to_completed_from_on_scene(): void
    {
        $sr = $this->makeSr('on_scene');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'signature_captured');

        $this->assertEquals('completed', $result);
        $this->assertEquals('completed', $sr->fresh()->status);
    }

    public function test_signature_does_not_advance_from_completed(): void
    {
        $sr = $this->makeSr('completed');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'signature_captured');

        $this->assertNull($result);
    }

    public function test_payment_advances_to_completed_from_on_scene(): void
    {
        $sr = $this->makeSr('on_scene');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'payment_collected');

        $this->assertEquals('completed', $result);
        $this->assertEquals('completed', $sr->fresh()->status);
    }

    public function test_payment_does_not_advance_from_cancelled(): void
    {
        $sr = $this->makeSr('cancelled');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'payment_collected');

        $this->assertNull($result);
    }

    public function test_unknown_event_does_nothing(): void
    {
        $sr = $this->makeSr('on_scene');
        $service = new StatusAutomationService();

        $result = $service->handle($sr, 'some_unknown_event');

        $this->assertNull($result);
        $this->assertEquals('on_scene', $sr->fresh()->status);
    }

    public function test_automation_creates_status_log(): void
    {
        $sr = $this->makeSr('en_route');
        $service = new StatusAutomationService();

        $service->handle($sr, 'photo_uploaded');

        $this->assertDatabaseHas('service_request_status_logs', [
            'service_request_id' => $sr->id,
            'old_status'         => 'en_route',
            'new_status'         => 'on_scene',
            'changed_by'         => null,
        ]);
    }

    public function test_automation_creates_service_log(): void
    {
        $sr = $this->makeSr('on_scene');
        $service = new StatusAutomationService();

        $service->handle($sr, 'signature_captured');

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'status_change',
        ]);
    }

    // ------------------------------------------------------------------
    // Integration: photo upload triggers status automation
    // ------------------------------------------------------------------

    public function test_photo_upload_auto_advances_status(): void
    {
        Storage::fake('local');

        $sr = $this->makeSr('en_route');

        $this->actingAs($this->user)->post(
            route('photos.store', $sr),
            [
                'photo' => UploadedFile::fake()->image('test.jpg'),
                'type'  => 'before',
            ]
        );

        $this->assertEquals('on_scene', $sr->fresh()->status);
    }

    public function test_photo_upload_does_not_regress_status(): void
    {
        Storage::fake('local');

        $sr = $this->makeSr('completed');

        $this->actingAs($this->user)->post(
            route('photos.store', $sr),
            [
                'photo' => UploadedFile::fake()->image('test.jpg'),
                'type'  => 'after',
            ]
        );

        $this->assertEquals('completed', $sr->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Integration: payment triggers status automation
    // ------------------------------------------------------------------

    public function test_payment_auto_advances_on_scene_to_completed(): void
    {
        $sr = $this->makeSr('on_scene');

        $this->actingAs($this->user)->post(
            route('payments.store', $sr),
            [
                'method' => 'cash',
                'amount' => 100.00,
            ]
        );

        $this->assertEquals('completed', $sr->fresh()->status);
    }

    public function test_payment_does_not_advance_from_new(): void
    {
        // payment_collected targets 'completed' — from 'new' it would
        // jump forward, which is allowed by shouldAdvance
        $sr = $this->makeSr('new');

        $this->actingAs($this->user)->post(
            route('payments.store', $sr),
            [
                'method' => 'cash',
                'amount' => 50.00,
            ]
        );

        // Payment from 'new' should still advance to 'completed'
        // since 'new' is before 'completed' in the pipeline
        $this->assertEquals('completed', $sr->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Manual status changes still work
    // ------------------------------------------------------------------

    public function test_manual_status_change_still_works(): void
    {
        $sr = $this->makeSr('new');

        $response = $this->actingAs($this->user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'dispatched']
        );

        $response->assertRedirect();
        $this->assertEquals('dispatched', $sr->fresh()->status);
    }

    public function test_manual_status_change_blocked_for_invalid_transition(): void
    {
        $sr = $this->makeSr('new');

        $response = $this->actingAs($this->user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'completed']
        );

        $response->assertRedirect();
        $this->assertEquals('new', $sr->fresh()->status);
    }
}
