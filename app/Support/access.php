<?php

use App\Models\User;
use App\Services\Access\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

if (! function_exists('requirePageAccess')) {
    function requirePageAccess(string $pagePath, ?Request $request = null): ?RedirectResponse
    {
        return app(AccessControlService::class)->requirePageAccess($pagePath, $request);
    }
}

if (! function_exists('canAccessPage')) {
    function canAccessPage(string $pagePath, ?User $user = null): bool
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        return app(AccessControlService::class)->canAccessPage($user ?? $auth->user(), $pagePath);
    }
}