<?php

namespace App\Services\Access;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class PageAccessResolver
{
    /** @var array<string, string> */
    private array $namedGetRoutes;

    /** @var array<string, string> */
    private array $protectedGetPaths;

    public function __construct()
    {
        $this->namedGetRoutes = [];
        $this->protectedGetPaths = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! $this->isProtectedRoute($route) || ! $this->isGetRoute($route) || $this->shouldIgnore($route)) {
                continue;
            }

            $path = $this->normalizePath($route->uri());

            if ($route->getName() !== null) {
                $this->namedGetRoutes[$route->getName()] = $path;
            }

            $this->protectedGetPaths[$path] = $path;
        }
    }

    public function resolve(?Route $route): ?string
    {
        if ($route === null || ! $this->isProtectedRoute($route) || $this->shouldIgnore($route)) {
            return null;
        }

        if ($this->isGetRoute($route)) {
            return $this->normalizePath($route->uri());
        }

        foreach ($this->candidateRouteNames($route->getName()) as $candidateName) {
            if (isset($this->namedGetRoutes[$candidateName])) {
                return $this->namedGetRoutes[$candidateName];
            }
        }

        $uriSegments = explode('/', trim($route->uri(), '/'));

        while ($uriSegments !== []) {
            $candidate = $this->normalizePath(implode('/', $uriSegments));

            if (isset($this->protectedGetPaths[$candidate])) {
                return $candidate;
            }

            array_pop($uriSegments);
        }

        if (Str::startsWith($route->uri(), 'api/')) {
            $apiTrimmed = explode('/', trim(Str::after($route->uri(), 'api/'), '/'));

            while ($apiTrimmed !== []) {
                $candidate = $this->normalizePath(implode('/', $apiTrimmed));

                if (isset($this->protectedGetPaths[$candidate])) {
                    return $candidate;
                }

                array_pop($apiTrimmed);
            }
        }

        return null;
    }

    public function isProtectedRoute(Route $route): bool
    {
        $middleware = $route->gatherMiddleware();

        return in_array('auth', $middleware, true)
            || in_array('Illuminate\\Auth\\Middleware\\Authenticate', $middleware, true);
    }

    public function labelForPath(string $path): string
    {
        $segments = array_filter(explode('/', trim($path, '/')));
        $label = implode(' ', array_map(fn (string $segment): string => str_replace(['-', '{', '}'], [' ', '', ''], $segment), $segments));

        return Str::title($label === '' ? 'Dashboard' : $label);
    }

    private function isGetRoute(Route $route): bool
    {
        return in_array('GET', $route->methods(), true) || in_array('HEAD', $route->methods(), true);
    }

    private function shouldIgnore(Route $route): bool
    {
        $ignoredNames = [
            'access.denied',
            'logout',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.confirm',
            'password.update',
        ];

        $ignoredPaths = [
            '/up',
        ];

        return in_array($route->getName(), $ignoredNames, true)
            || in_array($this->normalizePath($route->uri()), $ignoredPaths, true);
    }

    /** @return list<string> */
    private function candidateRouteNames(?string $routeName): array
    {
        if ($routeName === null) {
            return [];
        }

        $name = Str::startsWith($routeName, 'api.') ? Str::after($routeName, 'api.') : $routeName;
        $segments = explode('.', $name);
        $action = array_pop($segments);
        $base = implode('.', $segments);

        $candidates = [$name];

        match ($action) {
            'store' => $candidates = array_merge($candidates, [$base.'.create', $base.'.index']),
            'update', 'destroy' => $candidates = array_merge($candidates, [$base.'.edit', $base.'.show', $base.'.index']),
            'edit', 'create', 'show', 'index' => $candidates[] = $base.'.'.$action,
            default => $candidates = array_merge($candidates, [$base.'.show', $base.'.edit', $base.'.index', $base.'.create']),
        };

        while (count($segments) > 1) {
            array_pop($segments);
            $prefix = implode('.', $segments);
            $candidates = array_merge($candidates, [
                $prefix.'.show',
                $prefix.'.edit',
                $prefix.'.index',
                $prefix.'.create',
            ]);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/'.trim($path, '/');

        return $normalized === '/' ? '/dashboard' : $normalized;
    }
}