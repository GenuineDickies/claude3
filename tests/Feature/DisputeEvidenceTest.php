<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\PaymentRecord;
use App\Models\ServiceLog;
use App\Models\ServicePhoto;
use App\Models\ServiceRequest;
use App\Models\ServiceSignature;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DisputeEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ServiceRequest $sr;

    private function makeDispatchReady(ServiceRequest $serviceRequest): void
    {
        Estimate::create([
            'service_request_id' => $serviceRequest->id,
            'estimate_number' => 'EST-DISPUTE-' . str_pad((string) (Estimate::count() + 1), 4, '0', STR_PAD_LEFT),
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
            'work_order_number' => 'WO-DISPUTE-' . str_pad((string) (WorkOrder::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'assigned_to' => 'Driver One',
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'phone'      => '5559876543',
            'is_active'  => true,
        ]);
        $this->sr = ServiceRequest::create([
            'customer_id'    => $customer->id,
            'status'         => 'new',
            'location'       => '123 Main St',
            'latitude'       => 28.5383,
            'longitude'      => -81.3792,
        ]);
    }

    // ── Photo Tests ─────────────────────────────────────────

    public function test_upload_photo(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->user)->post(
            route('photos.store', $this->sr),
            [
                'photo'   => UploadedFile::fake()->image('test.jpg', 800, 600),
                'caption' => 'Front tire damage',
                'type'    => 'before',
            ]
        );

        $response->assertRedirect(route('service-requests.show', $this->sr));

        $this->assertDatabaseHas('service_photos', [
            'service_request_id' => $this->sr->id,
            'caption'            => 'Front tire damage',
            'type'               => 'before',
            'uploaded_by'        => $this->user->id,
        ]);

        // Auto-logged
        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $this->sr->id,
            'event'              => 'photo_uploaded',
        ]);
    }

    public function test_upload_photo_validates_file_type(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->user)->post(
            route('photos.store', $this->sr),
            [
                'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                'type'  => 'during',
            ]
        );

        $response->assertSessionHasErrors('photo');
    }

    public function test_upload_photo_validates_type(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->user)->post(
            route('photos.store', $this->sr),
            [
                'photo' => UploadedFile::fake()->image('test.jpg'),
                'type'  => 'invalid',
            ]
        );

        $response->assertSessionHasErrors('type');
    }

    public function test_delete_photo(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->image('test.jpg')->store('photos/' . $this->sr->id, 'local');

        $photo = ServicePhoto::create([
            'service_request_id' => $this->sr->id,
            'file_path'          => $path,
            'type'               => 'during',
            'uploaded_by'        => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(
            route('photos.destroy', [$this->sr, $photo])
        );

        $response->assertRedirect(route('service-requests.show', $this->sr));
        $this->assertDatabaseMissing('service_photos', ['id' => $photo->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_view_photo(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->image('test.jpg')->store('photos/' . $this->sr->id, 'local');

        $photo = ServicePhoto::create([
            'service_request_id' => $this->sr->id,
            'file_path'          => $path,
            'type'               => 'during',
        ]);

        $response = $this->actingAs($this->user)->get(
            route('photos.show', [$this->sr, $photo])
        );

        $response->assertOk();
    }

    public function test_photo_wrong_sr_returns_404(): void
    {
        $otherSr = ServiceRequest::create([
            'customer_id' => $this->sr->customer_id,
            'status'      => 'new',
        ]);
        $photo = ServicePhoto::create([
            'service_request_id' => $otherSr->id,
            'file_path'          => 'fake/path.jpg',
            'type'               => 'during',
        ]);

        $response = $this->actingAs($this->user)->get(
            route('photos.show', [$this->sr, $photo])
        );

        $response->assertNotFound();
    }

    // ── Payment Record Tests ────────────────────────────────

    public function test_record_payment(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('payments.store', $this->sr),
            [
                'method'       => 'cash',
                'amount'       => 75.00,
                'reference'    => 'CASH-001',
                'collected_at' => '2026-02-24',
            ]
        );

        $response->assertRedirect(route('service-requests.show', $this->sr));
        $this->assertDatabaseHas('payment_records', [
            'service_request_id' => $this->sr->id,
            'method'             => 'cash',
            'amount'             => 75.00,
            'reference'          => 'CASH-001',
            'collected_by'       => $this->user->id,
        ]);

        // Auto-logged
        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $this->sr->id,
            'event'              => 'payment_collected',
        ]);
    }

    public function test_record_payment_validates_method(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('payments.store', $this->sr),
            ['method' => 'bitcoin', 'amount' => 50]
        );

        $response->assertSessionHasErrors('method');
    }

    public function test_record_payment_validates_amount(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('payments.store', $this->sr),
            ['method' => 'cash', 'amount' => 0]
        );

        $response->assertSessionHasErrors('amount');
    }

    public function test_delete_payment(): void
    {
        $payment = PaymentRecord::create([
            'service_request_id' => $this->sr->id,
            'method'             => 'card',
            'amount'             => 100,
            'collected_at'       => now(),
            'collected_by'       => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(
            route('payments.destroy', [$this->sr, $payment])
        );

        $response->assertRedirect(route('service-requests.show', $this->sr));
        $this->assertDatabaseMissing('payment_records', ['id' => $payment->id]);
    }

    public function test_payment_status_unpaid(): void
    {
        $this->assertEquals('unpaid', $this->sr->paymentStatus());
    }

    public function test_payment_status_paid(): void
    {
        $this->sr->update(['quoted_price' => 75]);
        PaymentRecord::create([
            'service_request_id' => $this->sr->id,
            'method' => 'cash', 'amount' => 75,
            'collected_at' => now(),
        ]);
        $this->sr->load('paymentRecords');

        $this->assertEquals('paid', $this->sr->paymentStatus());
    }

    public function test_payment_status_partial(): void
    {
        $this->sr->update(['quoted_price' => 100]);
        PaymentRecord::create([
            'service_request_id' => $this->sr->id,
            'method' => 'cash', 'amount' => 50,
            'collected_at' => now(),
        ]);
        $this->sr->load('paymentRecords');

        $this->assertEquals('partial', $this->sr->paymentStatus());
    }

    // ── Signature Tests ─────────────────────────────────────

    public function test_request_signature_creates_token(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('signatures.request', $this->sr)
        );

        $response->assertRedirect(route('service-requests.show', $this->sr));

        $sig = ServiceSignature::where('service_request_id', $this->sr->id)->first();
        $this->assertNotNull($sig);
        $this->assertNotEmpty($sig->token);
        $this->assertTrue($sig->token_expires_at->isFuture());
    }

    public function test_signing_page_loads(): void
    {
        $sig = ServiceSignature::create([
            'service_request_id' => $this->sr->id,
            'signature_data' => '',
            'signer_name' => '',
            'signed_at' => now(),
            'token' => 'test-token-abc123',
            'token_expires_at' => now()->addHours(4),
        ]);

        $response = $this->get(route('signature.show', 'test-token-abc123'));
        $response->assertOk();
        $response->assertSee('Please sign below');
    }

    public function test_signing_page_returns_410_when_expired(): void
    {
        ServiceSignature::create([
            'service_request_id' => $this->sr->id,
            'signature_data' => '',
            'signer_name' => '',
            'signed_at' => now(),
            'token' => 'expired-token',
            'token_expires_at' => now()->subHour(),
        ]);

        $response = $this->get(route('signature.show', 'expired-token'));
        $response->assertStatus(410);
    }

    public function test_signing_page_returns_410_when_already_signed(): void
    {
        ServiceSignature::create([
            'service_request_id' => $this->sr->id,
            'signature_data' => 'data:image/png;base64,abc123',
            'signer_name' => 'John Doe',
            'signed_at' => now(),
            'token' => 'signed-token',
            'token_expires_at' => now()->addHours(4),
        ]);

        $response = $this->get(route('signature.show', 'signed-token'));
        $response->assertStatus(410);
        $response->assertSee('Already Signed');
    }

    public function test_submit_signature(): void
    {
        $sig = ServiceSignature::create([
            'service_request_id' => $this->sr->id,
            'signature_data' => '',
            'signer_name' => '',
            'signed_at' => now(),
            'token' => 'submit-token',
            'token_expires_at' => now()->addHours(4),
        ]);

        $response = $this->post(route('signature.store', 'submit-token'), [
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'signer_name'    => 'Jane Customer',
        ]);

        $response->assertOk();
        $response->assertSee('Signature Received');

        $sig->refresh();
        $this->assertNotEmpty($sig->signature_data);
        $this->assertEquals('Jane Customer', $sig->signer_name);
        $this->assertNotNull($sig->ip_address);

        // Auto-logged
        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $this->sr->id,
            'event'              => 'signature_captured',
        ]);
    }

    public function test_submit_signature_rejects_expired(): void
    {
        ServiceSignature::create([
            'service_request_id' => $this->sr->id,
            'signature_data' => '',
            'signer_name' => '',
            'signed_at' => now(),
            'token' => 'expired-submit',
            'token_expires_at' => now()->subHour(),
        ]);

        $response = $this->post(route('signature.store', 'expired-submit'), [
            'signature_data' => 'data:image/png;base64,abc',
            'signer_name'    => 'Test',
        ]);

        $response->assertStatus(410);
    }

    public function test_submit_signature_rejects_already_signed(): void
    {
        ServiceSignature::create([
            'service_request_id' => $this->sr->id,
            'signature_data' => 'data:image/png;base64,existing',
            'signer_name' => 'Already Signed',
            'signed_at' => now(),
            'token' => 'already-signed-submit',
            'token_expires_at' => now()->addHours(4),
        ]);

        $response = $this->post(route('signature.store', 'already-signed-submit'), [
            'signature_data' => 'data:image/png;base64,new',
            'signer_name'    => 'Attacker',
        ]);

        $response->assertStatus(410);
    }

    // ── Service Log Tests ───────────────────────────────────

    public function test_add_manual_log_entry(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('service-logs.store', $this->sr),
            ['event' => 'note_added', 'details' => 'Called customer, no answer']
        );

        $response->assertRedirect(route('service-requests.show', $this->sr));
        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $this->sr->id,
            'event'              => 'note_added',
            'logged_by'          => $this->user->id,
        ]);
    }

    public function test_status_change_creates_service_log(): void
    {
        $this->makeDispatchReady($this->sr);

        $this->actingAs($this->user)->patch(
            route('service-requests.update', $this->sr),
            ['status' => 'dispatched', 'notes' => 'Driver assigned']
        );

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $this->sr->id,
            'event'              => 'status_change',
        ]);

        $log = ServiceLog::where('service_request_id', $this->sr->id)
            ->where('event', 'status_change')->first();
        $this->assertEquals('new', $log->details['old_status']);
        $this->assertEquals('dispatched', $log->details['new_status']);
    }

    // ── SR Show Page Integration ────────────────────────────

    public function test_show_page_displays_photo_section(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service-requests.show', $this->sr)
        );

        $response->assertOk();
        $response->assertSee('Photos');
    }

    public function test_show_page_displays_payment_section(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service-requests.show', $this->sr)
        );

        $response->assertOk();
        $response->assertSee('Payments');
        $response->assertSee('Unpaid');
    }

    public function test_show_page_displays_signature_section(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service-requests.show', $this->sr)
        );

        $response->assertOk();
        $response->assertSee('Customer Signature');
        $response->assertSee('Request Signature');
    }

    public function test_show_page_displays_activity_log(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service-requests.show', $this->sr)
        );

        $response->assertOk();
        $response->assertSee('Activity Log');
        $response->assertSee('View Evidence Package');
    }

    // ── Evidence Package ────────────────────────────────────

    public function test_evidence_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service-requests.evidence', $this->sr)
        );

        $response->assertOk();
        $response->assertSee('Evidence Package');
        $response->assertSee('Evidence Completeness');
    }

    public function test_evidence_page_shows_gps_data(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('service-requests.evidence', $this->sr)
        );

        $response->assertOk();
        $response->assertSee('28.5383');
        $response->assertSee('-81.3792');
    }

    public function test_evidence_completeness_score(): void
    {
        // SR has GPS, so at least 1 out of 7 checks should pass
        $response = $this->actingAs($this->user)->get(
            route('service-requests.evidence', $this->sr)
        );

        $response->assertOk();
        // GPS location is present, so should see checkmark
        $response->assertSee('GPS Location');
    }

    // ── Auth checks ─────────────────────────────────────────

    public function test_photo_routes_require_auth(): void
    {
        $this->post(route('photos.store', $this->sr))->assertRedirect(route('login'));
    }

    public function test_payment_routes_require_auth(): void
    {
        $this->post(route('payments.store', $this->sr))->assertRedirect(route('login'));
    }

    public function test_signature_request_requires_auth(): void
    {
        $this->post(route('signatures.request', $this->sr))->assertRedirect(route('login'));
    }

    public function test_evidence_page_requires_auth(): void
    {
        $this->get(route('service-requests.evidence', $this->sr))->assertRedirect(route('login'));
    }
}
