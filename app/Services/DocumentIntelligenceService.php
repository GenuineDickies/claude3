<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentIntelligenceService implements DocumentIntelligenceInterface
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.document_ai.api_key', '');
        $this->model = (string) config('services.document_ai.model', 'gpt-4o-mini');
    }

    /** {@inheritDoc} */
    public function analyze(string $textContent, ?string $base64Image = null, ?string $mimeType = null): array
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('Document AI API key is not configured.');
        }

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt()],
            ['role' => 'user',   'content' => $this->buildUserContent($textContent, $base64Image, $mimeType)],
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => $this->model,
                'messages'        => $messages,
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.1,
                'max_tokens'      => 4096,
            ]);

        if ($response->failed()) {
            Log::debug('Document AI API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException(
                'Document AI API request failed with status ' . $response->status()
            );
        }

        $content = $response->json('choices.0.message.content', '');

        $parsed = json_decode($content, true);

        if (! is_array($parsed)) {
            Log::debug('Document AI returned non-JSON content', ['content' => $content]);
            throw new \RuntimeException('Document AI returned invalid JSON response.');
        }

        return $this->normalizeResult($parsed);
    }

    private function buildSystemPrompt(): string
    {
        $categories = implode(', ', Document::CATEGORIES);

        return <<<PROMPT
You are a document analysis assistant for a roadside-assistance business. Analyze the provided document and return a JSON object with exactly these keys:

1. "category" — One of: {$categories}. Choose the best match for this document type.
2. "summary" — A 2-4 sentence plain-text summary of what this document is and its key details.
3. "tags" — An array of 3-8 relevant keyword tags (lowercase, no special characters).
4. "extracted_data" — A flat object of key-value pairs extracted from the document. Include any of these that are present:
   - vendor_name, customer_name, date, due_date, expiration_date
   - total_amount, subtotal, tax_amount, currency
   - invoice_number, receipt_number, policy_number, contract_number
   - vin, license_plate, vehicle_make, vehicle_model, vehicle_year
   - address, phone, email
   - line_items (for documents with a FEW items only — maximum 10. For spreadsheets or documents with many rows, provide a count like "transaction_count": 150 and "date_range": "2020-01-01 to 2020-12-31" instead of individual line items.)
   - Any other clearly identifiable key data points from the document.
   Omit keys that are not present in the document. Use null for values that appear but are unreadable.
5. "confidence" — A float from 0.0 to 1.0 indicating your overall confidence in the extraction accuracy.

Return ONLY valid JSON. No markdown, no explanations outside the JSON. Keep the response concise.
PROMPT;
    }

    /**
     * Build the user message content array (text-only or multimodal with image).
     *
     * @return string|array<int, array<string, mixed>>
     */
    private function buildUserContent(string $textContent, ?string $base64Image, ?string $mimeType): string|array
    {
        // Vision mode: send image (optionally with any extracted text)
        if ($base64Image !== null && $mimeType !== null) {
            $parts = [];

            if ($textContent !== '') {
                $parts[] = [
                    'type' => 'text',
                    'text' => "Extracted text from the document:\n\n" . mb_substr($textContent, 0, 8000),
                ];
            }

            $parts[] = [
                'type'      => 'image_url',
                'image_url' => [
                    'url'    => "data:{$mimeType};base64,{$base64Image}",
                    'detail' => 'high',
                ],
            ];

            $parts[] = [
                'type' => 'text',
                'text' => 'Analyze this document image and extract all relevant data.',
            ];

            return $parts;
        }

        // Text-only mode
        return "Analyze the following document text and extract all relevant data:\n\n" . mb_substr($textContent, 0, 12000);
    }

    /**
     * Normalize the parsed API response into the expected structure.
     *
     * @param  array<string, mixed>  $parsed
     * @return array{category: string, summary: string, tags: string[], extracted_data: array<string, mixed>, confidence: float}
     */
    private function normalizeResult(array $parsed): array
    {
        $category = $parsed['category'] ?? 'other';
        if (! in_array($category, Document::CATEGORIES, true)) {
            $category = 'other';
        }

        return [
            'category'       => $category,
            'summary'        => (string) ($parsed['summary'] ?? ''),
            'tags'           => is_array($parsed['tags'] ?? null) ? array_values($parsed['tags']) : [],
            'extracted_data' => is_array($parsed['extracted_data'] ?? null) ? $parsed['extracted_data'] : [],
            'confidence'     => min(1.0, max(0.0, (float) ($parsed['confidence'] ?? 0.0))),
        ];
    }
}
