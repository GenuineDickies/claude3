<?php

namespace App\Services;

interface DocumentIntelligenceInterface
{
    /**
     * Analyze document content and return structured extraction results.
     *
     * @param  string       $textContent  Extracted text from the document (may be empty for image-only)
     * @param  string|null  $base64Image  Base64-encoded image data for vision analysis
     * @param  string|null  $mimeType     MIME type of the image (e.g. image/jpeg, image/png)
     *
     * @return array{
     *   category: string,
     *   summary: string,
     *   tags: string[],
     *   extracted_data: array<string, mixed>,
     *   confidence: float
     * }
     *
     * @throws \RuntimeException  When the API call fails after internal error handling
     */
    public function analyze(string $textContent, ?string $base64Image = null, ?string $mimeType = null): array;
}
