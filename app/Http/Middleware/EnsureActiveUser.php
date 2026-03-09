<?php

namespace App\Http\Middleware;

use App\Services\Access\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            $this->auditLogger->log('disabled_user_blocked', $user, null, $request);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been disabled. Contact an administrator.',
            ]);
        }

        return $next($request);
    }
}