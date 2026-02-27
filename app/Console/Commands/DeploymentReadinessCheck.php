<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Throwable;

class DeploymentReadinessCheck extends Command
{
    protected $signature = 'deploy:check {--strict : Treat warnings as failures}';

    protected $description = 'Validate production deployment readiness for this application';

    public function handle(): int
    {
        $failures = [];
        $warnings = [];

        $this->line('Running deployment readiness checks...');

        $appKey = (string) Config::get('app.key', '');
        if ($appKey === '') {
            $failures[] = 'APP_KEY is missing.';
        }

        if ((bool) Config::get('app.debug', false)) {
            $failures[] = 'APP_DEBUG must be false for production deployments.';
        }

        $queueConnection = (string) Config::get('queue.default', 'sync');
        if ($queueConnection === 'sync') {
            $warnings[] = 'QUEUE_CONNECTION is sync; background jobs will run inline.';
        }

        $storagePath = storage_path('app/private');
        if (! File::exists($storagePath)) {
            $warnings[] = 'storage/app/private does not exist yet.';
        } elseif (! is_writable($storagePath)) {
            $failures[] = 'storage/app/private is not writable.';
        }

        $publicStoragePath = public_path('storage');
        if (! File::exists($publicStoragePath)) {
            $warnings[] = 'public/storage symlink is missing (run: php artisan storage:link).';
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $failures[] = 'Database connection failed: '.$e->getMessage();
        }

        $scheduledEvents = Schedule::events();
        if ($scheduledEvents === []) {
            $warnings[] = 'No scheduled tasks detected.';
        }

        $telnyxApiKey = (string) env('TELNYX_API_KEY', '');
        $telnyxFromNumber = (string) env('TELNYX_FROM_NUMBER', '');
        if (($telnyxApiKey === '') xor ($telnyxFromNumber === '')) {
            $warnings[] = 'Telnyx is partially configured. Set both TELNYX_API_KEY and TELNYX_FROM_NUMBER.';
        }

        foreach ($failures as $failure) {
            $this->error('FAIL: '.$failure);
        }

        foreach ($warnings as $warning) {
            $this->warn('WARN: '.$warning);
        }

        if ($failures === [] && $warnings === []) {
            $this->info('PASS: Deployment readiness checks passed.');
            return self::SUCCESS;
        }

        $strict = (bool) $this->option('strict');
        if ($failures !== []) {
            $this->newLine();
            $this->error('Deployment readiness check failed.');
            return self::FAILURE;
        }

        if ($strict && $warnings !== []) {
            $this->newLine();
            $this->error('Deployment readiness check failed in strict mode due to warnings.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Deployment readiness check completed with warnings.');

        return self::SUCCESS;
    }
}
