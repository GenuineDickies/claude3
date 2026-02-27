<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class DeploymentReadinessCheckTest extends TestCase
{
    public function test_deploy_check_fails_when_app_key_is_missing(): void
    {
        $originalKey = Config::get('app.key');
        $originalDebug = Config::get('app.debug');

        Config::set('app.key', '');
        Config::set('app.debug', false);

        try {
            $this->artisan('deploy:check')->assertExitCode(1);
        } finally {
            Config::set('app.key', $originalKey);
            Config::set('app.debug', $originalDebug);
        }
    }

    public function test_deploy_check_strict_mode_fails_on_warnings(): void
    {
        $originalKey = Config::get('app.key');
        $originalDebug = Config::get('app.debug');
        $originalQueue = Config::get('queue.default');

        Config::set('app.key', $originalKey ?: 'base64:testtesttesttesttesttesttesttesttesttest=');
        Config::set('app.debug', false);
        Config::set('queue.default', 'sync');

        try {
            $this->artisan('deploy:check --strict')->assertExitCode(1);
        } finally {
            Config::set('app.key', $originalKey);
            Config::set('app.debug', $originalDebug);
            Config::set('queue.default', $originalQueue);
        }
    }
}
