<?php

namespace Tests\Unit;

use App\Jobs\ImportDocumentTransactionsJob;
use App\Jobs\ProcessDocumentIntelligenceJob;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Warranty;
use App\Models\ServiceRequest;
use App\Services\DocumentIntelligenceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessDocumentIntelligenceJobTest extends TestCase
{
    use RefreshDatabase;

    private function createDocument(array $overrides = []): Document
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

        $warranty = Warranty::create([
            'service_request_id'  => $sr->id,
            'part_name'           => 'Alternator',
            'install_date'        => '2026-02-20',
            'warranty_months'     => 12,
            'warranty_expires_at' => '2027-02-20',
        ]);

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

    public function test_job_sets_status_to_processing_then_completed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test/file.jpg', 'fake-image-content');

        $document = $this->createDocument([
            'file_path'  => 'documents/test/file.jpg',
            'mime_type'  => 'image/jpeg',
            'file_size'  => 500,
        ]);

        $mockService = $this->mock(DocumentIntelligenceInterface::class);
        $mockService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'category'       => 'receipt',
                'summary'        => 'A test receipt.',
                'tags'           => ['receipt', 'test'],
                'extracted_data' => ['vendor_name' => 'TestCo'],
                'confidence'     => 0.88,
            ]);

        (new ProcessDocumentIntelligenceJob($document))->handle($mockService);

        $document->refresh();
        $this->assertEquals('completed', $document->ai_status);
        $this->assertEquals('A test receipt.', $document->ai_summary);
        $this->assertEquals(['receipt', 'test'], $document->ai_tags);
        $this->assertEquals('receipt', $document->ai_suggested_category);
        $this->assertEquals(0.88, $document->ai_confidence);
        $this->assertNotNull($document->ai_processed_at);
    }

    public function test_job_auto_updates_category_when_other_and_high_confidence(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test/file.jpg', 'fake-image-content');

        $document = $this->createDocument([
            'file_path'  => 'documents/test/file.jpg',
            'mime_type'  => 'image/jpeg',
            'file_size'  => 500,
            'category'   => 'other',
        ]);

        $mockService = $this->mock(DocumentIntelligenceInterface::class);
        $mockService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'category'       => 'invoice',
                'summary'        => 'An invoice.',
                'tags'           => ['invoice'],
                'extracted_data' => [],
                'confidence'     => 0.85,
            ]);

        (new ProcessDocumentIntelligenceJob($document))->handle($mockService);

        $document->refresh();
        $this->assertEquals('invoice', $document->category);
    }

    public function test_job_does_not_overwrite_category_when_not_other(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test/file.jpg', 'fake-image-content');

        $document = $this->createDocument([
            'file_path'  => 'documents/test/file.jpg',
            'mime_type'  => 'image/jpeg',
            'file_size'  => 500,
            'category'   => 'receipt',
        ]);

        $mockService = $this->mock(DocumentIntelligenceInterface::class);
        $mockService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'category'       => 'invoice',
                'summary'        => 'Actually an invoice.',
                'tags'           => ['invoice'],
                'extracted_data' => [],
                'confidence'     => 0.95,
            ]);

        (new ProcessDocumentIntelligenceJob($document))->handle($mockService);

        $document->refresh();
        $this->assertEquals('receipt', $document->category); // Should NOT change
        $this->assertEquals('invoice', $document->ai_suggested_category);
    }

    public function test_job_fails_gracefully_when_file_not_found(): void
    {
        Storage::fake('local');
        // Don't put any file on disk

        $document = $this->createDocument();

        $mockService = $this->mock(DocumentIntelligenceInterface::class);
        $mockService->shouldNotReceive('analyze');

        (new ProcessDocumentIntelligenceJob($document))->handle($mockService);

        $document->refresh();
        $this->assertEquals('failed', $document->ai_status);
        $this->assertStringContainsString('not found', $document->ai_error);
    }

    public function test_job_skips_unsupported_file_types(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test/file.zip', 'fake-zip');

        $document = $this->createDocument([
            'file_path'  => 'documents/test/file.zip',
            'mime_type'  => 'application/zip',
            'file_size'  => 1024,
        ]);

        $mockService = $this->mock(DocumentIntelligenceInterface::class);
        $mockService->shouldNotReceive('analyze');

        (new ProcessDocumentIntelligenceJob($document))->handle($mockService);

        $document->refresh();
        $this->assertEquals('completed', $document->ai_status);
        $this->assertStringContainsString('not supported', $document->ai_summary);
    }

    public function test_job_processes_word_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test/file.docx', 'fake-docx');

        $document = $this->createDocument([
            'file_path'  => 'documents/test/file.docx',
            'mime_type'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size'  => 1024,
        ]);

        $mockService = $this->mock(DocumentIntelligenceInterface::class);
        $mockService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'category'       => 'invoice',
                'summary'        => 'A word doc invoice.',
                'tags'           => ['invoice'],
                'extracted_data' => [],
                'confidence'     => 0.9,
            ]);

        (new ProcessDocumentIntelligenceJob($document))->handle($mockService);

        $document->refresh();
        $this->assertEquals('completed', $document->ai_status);
        $this->assertEquals('A word doc invoice.', $document->ai_summary);
    }

    public function test_job_processes_spreadsheet_documents(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::disk('local')->put('documents/test/file.xlsx', 'fake-xlsx');

        $document = $this->createDocument([
            'file_path'  => 'documents/test/file.xlsx',
            'mime_type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'file_size'  => 1024,
        ]);

        $mockService = $this->mock(DocumentIntelligenceInterface::class);
        $mockService->shouldReceive('analyze')
            ->once()
            ->andReturn([
                'category'       => 'expense',
                'summary'        => 'An expense spreadsheet.',
                'tags'           => ['expense'],
                'extracted_data' => [],
                'confidence'     => 0.85,
            ]);

        (new ProcessDocumentIntelligenceJob($document))->handle($mockService);

        $document->refresh();
        $this->assertEquals('completed', $document->ai_status);
        $this->assertEquals('An expense spreadsheet.', $document->ai_summary);
        Queue::assertPushed(ImportDocumentTransactionsJob::class, function (ImportDocumentTransactionsJob $job) use ($document): bool {
            return $job->document->is($document) && $job->spreadsheetText !== '';
        });
    }

    public function test_failed_method_records_error(): void
    {
        $document = $this->createDocument();

        $job = new ProcessDocumentIntelligenceJob($document);
        $job->failed(new \RuntimeException('API connection failed'));

        $document->refresh();
        $this->assertEquals('failed', $document->ai_status);
        $this->assertStringContainsString('API connection failed', $document->ai_error);
    }
}
