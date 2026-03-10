<?php

namespace App\Http\Middleware;

use App\Services\Access\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks disabled accounts even when they still hold a valid authenticated session.
 */
class EnsureActiveUser
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * Force logout and invalidate the session when the authenticated user is disabled.
     *
     * Side effect: writes an audit log entry for the blocked session.
     */
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