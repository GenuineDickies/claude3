<?php

namespace App\Http\Middleware;

use App\Services\Access\PageAccessResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePageAccess
{
    public function __construct(private readonly PageAccessResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route()?->getName() === 'access.denied') {
            return $next($request);
        }

        $pagePath = $this->resolver->resolve($request->route());

        if ($pagePath === null) {
            return $next($request);
        }

        $redirect = requirePageAccess($pagePath, $request);

        return $redirect ?? $next($request);
    }
}