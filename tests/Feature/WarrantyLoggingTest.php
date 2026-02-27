<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createServiceRequest(): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'phone'      => '5551234567',
            'is_active'  => true,
        ]);

        return ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);
    }

    public function test_warranty_creation_logs_warranty_added_event(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $this->actingAs($user)->post(route('warranties.store', $sr), [
            'part_name'       => 'Alternator',
            'install_date'    => '2026-02-20',
            'warranty_months' => 12,
        ]);

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'warranty_added',
            'logged_by'          => $user->id,
        ]);

        // Verify NOT logged as note_added
        $this->assertDatabaseMissing('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'note_added',
        ]);
    }

    public function test_warranty_update_logs_warranty_updated_event(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $warranty = Warranty::create([
            'service_request_id' => $sr->id,
            'part_name'          => 'Starter Motor',
            'install_date'       => '2026-01-15',
            'warranty_months'    => 6,
            'warranty_expires_at' => '2026-07-15',
            'created_by'         => $user->id,
        ]);

        $this->actingAs($user)->put(route('warranties.update', [$sr, $warranty]), [
            'part_name'       => 'Starter Motor (Remanufactured)',
            'install_date'    => '2026-01-15',
            'warranty_months' => 12,
        ]);

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'warranty_updated',
            'logged_by'          => $user->id,
        ]);
    }

    public function test_warranty_deletion_logs_warranty_deleted_event(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $warranty = Warranty::create([
            'service_request_id' => $sr->id,
            'part_name'          => 'Battery',
            'install_date'       => '2026-02-01',
            'warranty_months'    => 24,
            'warranty_expires_at' => '2028-02-01',
            'created_by'         => $user->id,
        ]);

        $this->actingAs($user)->delete(route('warranties.destroy', [$sr, $warranty]));

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'warranty_deleted',
            'logged_by'          => $user->id,
        ]);
    }

    public function test_warranty_event_labels_are_defined(): void
    {
        $this->assertArrayHasKey('warranty_added', ServiceLog::EVENT_LABELS);
        $this->assertArrayHasKey('warranty_updated', ServiceLog::EVENT_LABELS);
        $this->assertArrayHasKey('warranty_deleted', ServiceLog::EVENT_LABELS);

        $this->assertContains('warranty_added', ServiceLog::EVENTS);
        $this->assertContains('warranty_updated', ServiceLog::EVENTS);
        $this->assertContains('warranty_deleted', ServiceLog::EVENTS);
    }

    public function test_warranty_event_labels_human_readable(): void
    {
        $log = new ServiceLog(['event' => 'warranty_added']);
        $this->assertEquals('Warranty Added', $log->eventLabel());

        $log = new ServiceLog(['event' => 'warranty_updated']);
        $this->assertEquals('Warranty Updated', $log->eventLabel());

        $log = new ServiceLog(['event' => 'warranty_deleted']);
        $this->assertEquals('Warranty Deleted', $log->eventLabel());
    }
}
