<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Services\DocumentIntelligenceService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentIntelligenceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.document_ai.api_key' => 'test-api-key',
            'services.document_ai.model'   => 'gpt-4o-mini',
        ]);
    }

    public function test_analyze_returns_structured_result_for_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'category'       => 'invoice',
                            'summary'        => 'An invoice from AutoZone for brake pads.',
                            'tags'           => ['invoice', 'auto-parts', 'brake-pads'],
                            'extracted_data' => [
                                'vendor_name'  => 'AutoZone',
                                'total_amount' => '49.99',
                            ],
                            'confidence' => 0.95,
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $service = new DocumentIntelligenceService();
        $result = $service->analyze('Invoice #1234 from AutoZone. Total: $49.99');

        $this->assertEquals('invoice', $result['category']);
        $this->assertStringContains('AutoZone', $result['summary']);
        $this->assertIsArray($result['tags']);
        $this->assertContains('invoice', $result['tags']);
        $this->assertEquals('AutoZone', $result['extracted_data']['vendor_name']);
        $this->assertEquals(0.95, $result['confidence']);

        Http::assertSentCount(1);
    }

    public function test_analyze_normalizes_invalid_category_to_other(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'category'       => 'some-invalid-category',
                            'summary'        => 'A document.',
                            'tags'           => [],
                            'extracted_data' => [],
                            'confidence'     => 0.5,
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $service = new DocumentIntelligenceService();
        $result = $service->analyze('Some text content');

        $this->assertEquals('other', $result['category']);
    }

    public function test_analyze_clamps_confidence_between_0_and_1(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'category'       => 'receipt',
                            'summary'        => 'A receipt.',
                            'tags'           => [],
                            'extracted_data' => [],
                            'confidence'     => 1.5,
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $service = new DocumentIntelligenceService();
        $result = $service->analyze('Receipt text');

        $this->assertEquals(1.0, $result['confidence']);
    }

    public function test_analyze_throws_on_api_failure(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('status 500');

        $service = new DocumentIntelligenceService();
        $service->analyze('Some text');
    }

    public function test_analyze_throws_when_no_api_key_configured(): void
    {
        config(['services.document_ai.api_key' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API key is not configured');

        $service = new DocumentIntelligenceService();
        $service->analyze('Some text');
    }

    public function test_analyze_throws_on_invalid_json_response(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'This is not JSON',
                    ],
                ]],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid JSON');

        $service = new DocumentIntelligenceService();
        $service->analyze('Some text');
    }

    /** Simple polyfill-style assertion — PHPUnit 10+ compatible. */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
