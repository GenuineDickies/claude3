<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentLineItem;
use App\Jobs\ImportDocumentTransactionsJob;
use App\Services\DocumentIntelligenceInterface;
use App\Services\DocumentMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Send an uploaded document to the AI service for analysis,
 * then write the structured results back to the Document record.
 */
class ProcessDocumentIntelligenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** Exponential backoff: 30s, 60s, 120s, 240s between retries */
    public function backoff(): array
    {
        return [30, 60, 120, 240];
    }

    public function __construct(
        public Document $document,
    ) {}

    public function handle(DocumentIntelligenceInterface $service): void
    {
        $this->document->update(['ai_status' => 'processing']);

        $disk = Storage::disk('local');

        if (! $disk->exists($this->document->file_path)) {
            $this->document->update([
                'ai_status' => 'failed',
                'ai_error'  => 'File not found on disk.',
            ]);

            return;
        }

        // Skip file types we cannot meaningfully analyze
        if (! $this->document->isPdf() && ! $this->document->isImage()
            && ! $this->document->isWord() && ! $this->document->isSpreadsheet()) {
            $this->document->update([
                'ai_status'       => 'completed',
                'ai_summary'      => 'Text extraction is not supported for this file type.',
                'ai_processed_at' => now(),
            ]);

            return;
        }

        $textContent = '';
        $base64Image = null;
        $mimeType = null;

        if ($this->document->isPdf()) {
            $textContent = $this->extractPdfText($disk->path($this->document->file_path));

            // If PDF has very little extractable text, fall back to vision
            if (mb_strlen(trim($textContent)) < 50) {
                $maxSize = (int) config('services.document_ai.max_file_size_for_vision', 10 * 1024 * 1024);

                if ($this->document->file_size <= $maxSize) {
                    $base64Image = base64_encode($disk->get($this->document->file_path));
                    $mimeType = $this->document->mime_type;
                }
            }
        } elseif ($this->document->isImage()) {
            $maxSize = (int) config('services.document_ai.max_file_size_for_vision', 10 * 1024 * 1024);

            if ($this->document->file_size > $maxSize) {
                $this->document->update([
                    'ai_status'       => 'completed',
                    'ai_summary'      => 'Image file exceeds the maximum size for vision analysis.',
                    'ai_processed_at' => now(),
                ]);

                return;
            }

            $base64Image = base64_encode($disk->get($this->document->file_path));
            $mimeType = $this->document->mime_type;
        } elseif ($this->document->isWord()) {
            $textContent = $this->extractWordText($disk->path($this->document->file_path));
        } elseif ($this->document->isSpreadsheet()) {
            $textContent = $this->extractSpreadsheetText($disk->path($this->document->file_path));
        }

        try {
            $result = $service->analyze($textContent, $base64Image, $mimeType);
        } catch (\RuntimeException $e) {
            // Rate-limited — release back to queue with exponential delay, don't count as failure
            if (str_contains($e->getMessage(), 'status 429')) {
                $delay = 30 * pow(2, $this->attempts());
                Log::warning('ProcessDocumentIntelligenceJob: Rate limited, retrying', [
                    'document_id' => $this->document->id,
                    'attempt'     => $this->attempts(),
                    'delay'       => $delay,
                ]);
                $this->document->update(['ai_status' => 'pending']);
                $this->release($delay);

                return;
            }

            Log::error('ProcessDocumentIntelligenceJob: AI analysis failed', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            Log::error('ProcessDocumentIntelligenceJob: AI analysis failed', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e; // Let the queue retry
        }

        $updates = [
            'ai_summary'            => $result['summary'],
            'ai_tags'               => $result['tags'],
            'ai_extracted_data'     => $result['extracted_data'],
            'ai_confidence'         => $result['confidence'],
            'ai_suggested_category' => $result['category'],
            'ai_status'             => 'completed',
            'ai_processed_at'       => now(),
            'ai_error'              => null,
        ];

        // Auto-update category only when user left it as "other" and AI is reasonably confident
        if ($this->document->category === 'other' && $result['confidence'] >= 0.7) {
            $updates['category'] = $result['category'];
        }

        $this->document->update($updates);

        // Extract line items from receipts/invoices into document_line_items
        $this->createLineItemsFromExtractedData($result['extracted_data']);

        // For inbox docs (no parent entity), attempt auto-matching
        // Skip matching for spreadsheets — they contain many transactions,
        // not a single entity to link to. Individual rows are handled by
        // ImportDocumentTransactionsJob instead.
        if ($this->document->isInbox() && ! $this->document->isSpreadsheet()) {
            app(DocumentMatchingService::class)->match($this->document->fresh());
        }

        // For spreadsheets, also attempt to parse individual transactions
        if ($this->document->isSpreadsheet() && $textContent !== '') {
            ImportDocumentTransactionsJob::dispatch($this->document, $textContent);
        }
    }

    /** Called when all retry attempts are exhausted. */
    public function failed(\Throwable $exception): void
    {
        $this->document->update([
            'ai_status' => 'failed',
            'ai_error'  => mb_substr($exception->getMessage(), 0, 1000),
        ]);

        Log::error('ProcessDocumentIntelligenceJob permanently failed', [
            'document_id' => $this->document->id,
            'error'       => $exception->getMessage(),
        ]);
    }

    /** Create DocumentLineItem records from AI-extracted line_items data. */
    private function createLineItemsFromExtractedData(array $extractedData): void
    {
        $lineItems = $extractedData['line_items'] ?? null;

        if (! is_array($lineItems) || empty($lineItems)) {
            return;
        }

        // Clear any existing line items from a previous analysis run
        $this->document->lineItems()->where('status', DocumentLineItem::STATUS_DRAFT)->delete();

        foreach ($lineItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $amount = $item['amount'] ?? null;
            if ($amount === null || ! is_numeric($amount)) {
                continue;
            }

            $category = $item['category'] ?? null;
            if ($category !== null && ! array_key_exists($category, \App\Models\Expense::CATEGORIES)) {
                $category = null;
            }

            $this->document->lineItems()->create([
                'description' => mb_substr($description, 0, 255),
                'quantity'    => is_numeric($item['quantity'] ?? null) ? $item['quantity'] : null,
                'unit_price'  => is_numeric($item['unit_price'] ?? null) ? $item['unit_price'] : null,
                'amount'      => round((float) $amount, 2),
                'category'    => $category,
                'status'      => DocumentLineItem::STATUS_DRAFT,
                'raw_data'    => $item,
            ]);
        }
    }

    /** Extract text from a PDF file using smalot/pdfparser. */
    private function extractPdfText(string $absolutePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($absolutePath);

            return $pdf->getText();
        } catch (\Throwable $e) {
            Log::debug('PDF text extraction failed, will try vision fallback', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            return '';
        }
    }

    /** Extract text from a DOC/DOCX file using PhpWord. */
    private function extractWordText(string $absolutePath): string
    {
        try {
            $phpWord = WordIOFactory::load($absolutePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractWordElement($element) . "\n";
                }
            }

            return trim($text);
        } catch (\Throwable $e) {
            Log::debug('Word text extraction failed', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            return '';
        }
    }

    /** Recursively extract text from a PhpWord element. */
    private function extractWordElement(mixed $element): string
    {
        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text)) {
                return $text;
            }
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $parts[] = $this->extractWordElement($child);
            }

            return implode(' ', array_filter($parts));
        }

        return '';
    }

    /** Extract text from an XLS/XLSX file using PhpSpreadsheet. */
    private function extractSpreadsheetText(string $absolutePath): string
    {
        try {
            $spreadsheet = SpreadsheetIOFactory::load($absolutePath);
            $text = '';
            $maxChars = 60000; // ~15k tokens — safe limit for AI analysis

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $text .= '--- ' . $sheet->getTitle() . " ---\n";

                foreach ($sheet->toArray(null, true, true, false) as $row) {
                    $cells = array_map(fn ($v) => $v !== null ? (string) $v : '', $row);
                    $line = implode(' | ', $cells);
                    if (trim($line, ' |') !== '') {
                        $text .= $line . "\n";
                    }

                    if (mb_strlen($text) >= $maxChars) {
                        $text .= "\n[... truncated — spreadsheet exceeds analysis limit ...]\n";
                        return trim($text);
                    }
                }

                $text .= "\n";
            }

            return trim($text);
        } catch (\Throwable $e) {
            Log::debug('Spreadsheet text extraction failed', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            return '';
        }
    }
}
