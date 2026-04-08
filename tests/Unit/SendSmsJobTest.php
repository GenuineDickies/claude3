<?php

namespace Tests\Unit;

use App\Jobs\SendSmsJob;
use App\Models\MessageTemplate;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

final class SendSmsJobTest extends TestCase
{
    use RefreshDatabase;

    private function template(): MessageTemplate
    {
        return MessageTemplate::create([
            'slug' => 'dispatch-update',
            'name' => 'Dispatch Update',
            'body' => 'Technician is on the way.',
            'category' => 'dispatch',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_handle_succeeds_when_sms_service_returns_success(): void
    {
        $template = $this->template();
        $job = new SendSmsJob(template: $template, to: '15551234567');

        /** @var SmsServiceInterface&\Mockery\MockInterface $sms */
        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function (MessageTemplate $calledTemplate, string $to): bool {
                return $calledTemplate->slug === 'dispatch-update' && $to === '15551234567';
            })
            ->andReturn([
                'success' => true,
                'message_id' => 'msg-123',
                'rendered_text' => 'Technician is on the way.',
                'error' => null,
            ]);

        $job->handle($sms);

        $this->assertSame(3, $job->tries);
        $this->assertSame(15, $job->backoff);
    }

    public function test_handle_throws_and_logs_when_sms_service_fails(): void
    {
        $template = $this->template();
        $job = new SendSmsJob(template: $template, to: '15551234567');

        /** @var SmsServiceInterface&\Mockery\MockInterface $sms */
        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldReceive('sendTemplate')
            ->once()
            ->andReturn([
                'success' => false,
                'message_id' => null,
                'rendered_text' => null,
                'error' => 'provider timeout',
            ]);

        Log::shouldReceive('warning')
            ->once()
            ->with(
                'SendSmsJob failed',
                Mockery::on(function (array $context): bool {
                    return $context['template'] === 'dispatch-update'
                        && $context['to'] === '***4567'
                        && $context['error'] === 'provider timeout';
                }),
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SMS send failed: provider timeout');

        $job->handle($sms);
    }

    public function test_handle_throws_unknown_error_and_keeps_retry_configuration(): void
    {
        $template = $this->template();
        $job = new SendSmsJob(template: $template, to: '15559870000');

        /** @var SmsServiceInterface&\Mockery\MockInterface $sms */
        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldReceive('sendTemplate')
            ->once()
            ->andReturn([
                'success' => false,
            ]);

        Log::shouldReceive('warning')
            ->once()
            ->with(
                'SendSmsJob failed',
                Mockery::on(function (array $context): bool {
                    return $context['to'] === '***0000'
                        && $context['error'] === 'unknown';
                }),
            );

        try {
            $job->handle($sms);
            $this->fail('Expected RuntimeException for failed SMS send.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('SMS send failed: unknown', $exception->getMessage());
        }

        $this->assertSame(3, $job->tries);
        $this->assertSame(15, $job->backoff);
    }
}
