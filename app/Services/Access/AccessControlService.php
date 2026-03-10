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

/**
 * Centralizes user-to-page authorization checks and administrator safety rules.
 *
 * This service is used by middleware, user administration flows, and sidebar
 * rendering so that page access decisions stay consistent across the app.
 */
class AccessControlService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * Determine whether a specific active user may open the given canonical page path.
     */
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

    /**
     * Return a redirect response when access should be denied, otherwise null.
     *
     * Side effect: writes an audit log entry for denied access attempts.
     */
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

    /**
     * Check whether the user currently holds the reserved Administrator role.
     */
    public function isAdministrator(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->roles->contains(fn (Role $role): bool => $role->isAdministrator());
    }

    /**
     * Return all registered pages the user can access, including all pages for administrators.
     */
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

    /**
     * Ensure the reserved Administrator role exists and return it.
     */
    public function administratorRole(): Role
    {
        return Role::query()->firstOrCreate(
            ['role_name' => 'Administrator'],
            ['description' => 'Full access to all registered pages and administration tools.'],
        );
    }

    /**
     * Count active administrators, optionally excluding one managed user.
     */
    public function administratorsCount(?int $excludingUserId = null): int
    {
        $query = $this->administratorRole()->users()->where('status', 'active');

        if ($excludingUserId !== null) {
            $query->whereKeyNot($excludingUserId);
        }

        return $query->count();
    }

    /**
     * Determine whether updating the user's roles would remove the final active administrator.
     *
     * @param  array<int, int|string>  $roleIds
     */
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

    /**
     * Normalize free-form user input into the dotted username format used by the app.
     */
    public function slugToUsername(string $value): string
    {
        $normalized = Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        return $normalized === '' ? 'user' : $normalized;
    }

    /**
     * Generate a unique username, appending a numeric suffix when needed.
     */
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