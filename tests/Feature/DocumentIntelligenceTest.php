<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentIntelligenceJob;
use App\Models\Customer;
use App\Models\Document;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createWarranty(): Warranty
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'phone'      => '5551234567',
            'is_active'  => true,
        ]);

        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);

        return Warranty::create([
            'service_request_id'  => $sr->id,
            'part_name'           => 'Alternator',
            'install_date'        => '2026-02-20',
            'warranty_months'     => 12,
            'warranty_expires_at' => '2027-02-20',
        ]);
    }

    private function createDocument(array $overrides = []): Document
    {
        $warranty = $this->createWarranty();

        return Document::create(array_merge([
            'documentable_type' => Warranty::class,
            'documentable_id'   => $warranty->id,
            'file_path'         => 'documents/test/file.pdf',
            'original_filename' => 'invoice.pdf',
            'mime_type'         => 'application/pdf',
            'file_size'         => 1024,
            'category'          => 'other',
            'ai_status'         => 'pending',
        ], $overrides));
    }

    // ── Upload dispatches job ──────────────────────────────────

    public function test_upload_dispatches_ai_job_when_enabled(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['services.document_ai.enabled' => true]);

        $user = $this->createUser();
        $warranty = $this->createWarranty();

        $this->actingAs($user)
            ->post(route('documents.store', $warranty), [
                'file'     => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
                'category' => 'invoice',
            ]);

        Queue::assertPushed(ProcessDocumentIntelligenceJob::class);
    }

    public function test_upload_does_not_dispatch_ai_job_when_disabled(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['services.document_ai.enabled' => false]);

        $user = $this->createUser();
        $warranty = $this->createWarranty();

        $this->actingAs($user)
            ->post(route('documents.store', $warranty), [
                'file'     => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
                'category' => 'invoice',
            ]);

        Queue::assertNotPushed(ProcessDocumentIntelligenceJob::class);
    }

    // ── Generic polymorphic route ──────────────────────────────

    public function test_generic_upload_works_for_valid_type(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['services.document_ai.enabled' => false]);

        $user = $this->createUser();
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'phone'      => '5551234567',
            'is_active'  => true,
        ]);

        $this->actingAs($user)
            ->post(route('documents.store-generic', ['type' => 'customer', 'id' => $customer->id]), [
                'file'     => UploadedFile::fake()->image('license.jpg', 800, 600),
                'category' => 'license',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Customer::class,
            'documentable_id'   => $customer->id,
            'category'          => 'license',
        ]);
    }

    public function test_generic_upload_rejects_invalid_type(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post('/documents/invalid-type/1', [
                'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();
    }

    public function test_generic_upload_returns_404_for_nonexistent_owner(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('documents.store-generic', ['type' => 'customer', 'id' => 99999]), [
                'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();
    }

    // ── Detail page ────────────────────────────────────────────

    public function test_detail_page_renders_for_completed_document(): void
    {
        $user = $this->createUser();
        $document = $this->createDocument([
            'ai_status'             => 'completed',
            'ai_summary'            => 'An invoice from AutoZone.',
            'ai_tags'               => ['invoice', 'auto-parts'],
            'ai_extracted_data'     => ['vendor_name' => 'AutoZone', 'total_amount' => '49.99'],
            'ai_suggested_category' => 'invoice',
            'ai_confidence'         => 0.92,
            'ai_processed_at'       => now(),
        ]);

        $this->actingAs($user)
            ->get(route('documents.detail', $document))
            ->assertOk()
            ->assertSee('An invoice from AutoZone.')
            ->assertSee('invoice')
            ->assertSee('auto-parts')
            ->assertSee('AutoZone')
            ->assertSee('49.99');
    }

    public function test_detail_page_shows_error_for_failed_document(): void
    {
        $user = $this->createUser();
        $document = $this->createDocument([
            'ai_status' => 'failed',
            'ai_error'  => 'Connection timed out',
        ]);

        $this->actingAs($user)
            ->get(route('documents.detail', $document))
            ->assertOk()
            ->assertSee('Connection timed out');
    }

    // ── Reanalyze ──────────────────────────────────────────────

    public function test_reanalyze_resets_status_and_dispatches_job(): void
    {
        Queue::fake();
        config(['services.document_ai.enabled' => true]);

        $user = $this->createUser();
        $document = $this->createDocument([
            'ai_status' => 'failed',
            'ai_error'  => 'Previous error',
        ]);

        $this->actingAs($user)
            ->post(route('documents.reanalyze', $document))
            ->assertRedirect();

        $document->refresh();
        $this->assertEquals('pending', $document->ai_status);
        $this->assertNull($document->ai_error);

        Queue::assertPushed(ProcessDocumentIntelligenceJob::class);
    }

    // ── Accept category ────────────────────────────────────────

    public function test_accept_category_updates_document_category(): void
    {
        $user = $this->createUser();
        $document = $this->createDocument([
            'ai_status'             => 'completed',
            'ai_suggested_category' => 'invoice',
            'category'              => 'other',
        ]);

        $this->actingAs($user)
            ->post(route('documents.accept-category', $document))
            ->assertRedirect();

        $document->refresh();
        $this->assertEquals('invoice', $document->category);
    }

    // ── Auth ───────────────────────────────────────────────────

    public function test_document_intelligence_routes_require_authentication(): void
    {
        $document = $this->createDocument();

        $this->get(route('documents.detail', $document))->assertRedirect(route('login'));
        $this->post(route('documents.reanalyze', $document))->assertRedirect(route('login'));
        $this->post(route('documents.accept-category', $document))->assertRedirect(route('login'));
    }
}
