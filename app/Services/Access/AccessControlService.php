<?php

namespace App\Services\Access;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccessControlService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function canAccessPage(?User $user, string $pagePath): bool
    {
        if (! $user instanceof User || ! $user->isActive()) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        $page = Page::query()->where('page_path', $pagePath)->first();

        if (! $page) {
            return false;
        }

        return $user->roles()
            ->whereHas('pages', fn ($query) => $query->whereKey($page->id))
            ->exists();
    }

    public function requirePageAccess(string $pagePath, ?Request $request = null): ?RedirectResponse
    {
        $user = Auth::user();

        if ($this->canAccessPage($user, $pagePath)) {
            return null;
        }

        $this->auditLogger->log('access_denied', $user, [
            'page_path' => $pagePath,
            'route_name' => $request?->route()?->getName(),
        ], $request);

        return redirect()
            ->route('access.denied', ['page' => $pagePath])
            ->with('error', 'You do not have access to that page.');
    }

    public function isAdministrator(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->roles->contains(fn (Role $role): bool => $role->isAdministrator());
    }

    public function accessiblePagesFor(?User $user): Collection
    {
        if (! $user instanceof User) {
            return collect();
        }

        if ($this->isAdministrator($user)) {
            return Page::query()->orderBy('page_name')->get();
        }

        return Page::query()
            ->whereHas('roles.users', fn ($query) => $query->whereKey($user->id))
            ->orderBy('page_name')
            ->get();
    }

    public function administratorRole(): Role
    {
        return Role::query()->firstOrCreate(
            ['role_name' => 'Administrator'],
            ['description' => 'Full access to all registered pages and administration tools.'],
        );
    }

    public function administratorsCount(?int $excludingUserId = null): int
    {
        $query = $this->administratorRole()->users()->where('status', 'active');

        if ($excludingUserId !== null) {
            $query->whereKeyNot($excludingUserId);
        }

        return $query->count();
    }

    /** @param array<int, int|string> $roleIds */
    public function wouldLeaveSystemWithoutAdministrator(User $user, array $roleIds): bool
    {
        $administratorRoleId = $this->administratorRole()->id;
        $nextRoleIds = collect($roleIds)->map(fn ($roleId): int => (int) $roleId);

        if ($nextRoleIds->contains($administratorRoleId)) {
            return false;
        }

        return $user->roles->contains('id', $administratorRoleId)
            && $this->administratorsCount($user->id) === 0;
    }

    public function slugToUsername(string $value): string
    {
        $normalized = Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        return $normalized === '' ? 'user' : $normalized;
    }

    public function uniqueUsername(string $value, ?int $ignoreUserId = null): string
    {
        $base = $this->slugToUsername($value);
        $candidate = $base;
        $suffix = 1;

        while (User::query()
            ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->where('username', $candidate)
            ->exists()) {
            $suffix++;
            $candidate = $base.'.'.$suffix;
        }

        return $candidate;
    }
}