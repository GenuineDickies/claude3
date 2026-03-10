<?php

namespace Tests\Unit;

use App\Support\RequestPath;
use Illuminate\Http\Request;
use Tests\TestCase;

final class RequestPathTest extends TestCase
{
    public function test_prefixed_returns_root_relative_path_when_app_is_not_in_subdirectory(): void
    {
        $request = Request::create('/service-requests/create', 'GET');

        $this->assertSame('/api/customers/search', RequestPath::prefixed($request, '/api/customers/search'));
    }

    public function test_prefixed_preserves_subdirectory_base_path(): void
    {
        $request = Request::create('/webhook-proxy/service-requests/create', 'GET', [], [], [], [
            'SCRIPT_NAME' => '/webhook-proxy/index.php',
            'PHP_SELF' => '/webhook-proxy/index.php',
            'REQUEST_URI' => '/webhook-proxy/service-requests/create',
        ]);

        $this->assertSame('/webhook-proxy/api/customers/search', RequestPath::prefixed($request, '/api/customers/search'));
    }
}