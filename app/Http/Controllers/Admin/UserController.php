<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Services\Access\AccessControlService;
use App\Services\Access\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Manages administrative CRUD actions for internal user accounts and role assignment.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly AccessControlService $accessControl,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * Show the user index with search and status filters.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->value();

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'disabled'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show the create form with the current role list.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('role_name')->get(),
        ]);
    }

    /**
     * Create a user, hash the password, attach roles, and audit-log the action.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $roleIds = array_map('intval', Arr::wrap($validated['role_ids'] ?? []));

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        $user->roles()->sync($roleIds);
        $this->syncTechnicianProfile($user, $roleIds);

        $this->auditLogger->log('user_created', $request->user(), [
            'managed_user_id' => $user->id,
            'role_ids' => $roleIds,
            'status' => $user->status,
        ], $request);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the edit form for a managed user and preload assigned roles.
     */
    public function edit(User $user): View
    {
        $user->load('roles');

        return view('admin.users.edit', [
            'managedUser' => $user,
            'roles' => Role::query()->orderBy('role_name')->get(),
        ]);
    }

    /**
     * Update identity, optional password, status, and role assignments with administrator safeguards.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $roleIds = array_map('intval', Arr::wrap($validated['role_ids'] ?? []));
        $nextStatus = $validated['status'];

        if ($this->accessControl->wouldLeaveSystemWithoutAdministrator($user, $roleIds)) {
            return back()->withErrors([
                'role_ids' => 'The last administrator cannot lose the Administrator role.',
            ])->withInput();
        }

        if ($nextStatus !== 'active' && $user->isAdministrator() && $this->accessControl->administratorsCount($user->id) === 0) {
            return back()->withErrors([
                'status' => 'The last administrator cannot be disabled.',
            ])->withInput();
        }

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $nextStatus,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->roles()->sync($roleIds);
        $this->syncTechnicianProfile($user, $roleIds);

        $this->auditLogger->log('user_updated', $request->user(), [
            'managed_user_id' => $user->id,
            'role_ids' => $roleIds,
            'status' => $user->status,
        ], $request);

        return redirect()->route('admin.users.edit', $user)->with('success', 'User updated successfully.');
    }

    /**
     * Toggle a user's active state while preventing the final administrator from being disabled.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $nextStatus = $user->status === 'active' ? 'disabled' : 'active';

        if ($nextStatus === 'disabled' && $user->isAdministrator() && $this->accessControl->administratorsCount($user->id) === 0) {
            return back()->withErrors([
                'status' => 'The last administrator cannot be disabled.',
            ]);
        }

        $user->update(['status' => $nextStatus]);

        $this->auditLogger->log('user_status_toggled', $request->user(), [
            'managed_user_id' => $user->id,
            'status' => $nextStatus,
        ], $request);

        return back()->with('success', 'User status updated.');
    }

    /**
     * Create or remove the TechnicianProfile when the Technician role is added or removed.
     *
     * @param  array<int>  $roleIds
     */
    private function syncTechnicianProfile(User $user, array $roleIds): void
    {
        $hasTechnicianRole = Role::whereIn('id', $roleIds)
            ->get()
            ->contains(fn (Role $r) => $r->isTechnician());

        if ($hasTechnicianRole && ! $user->technicianProfile) {
            TechnicianProfile::create(['user_id' => $user->id]);
        } elseif (! $hasTechnicianRole && $user->technicianProfile) {
            $user->technicianProfile->delete();
        }
    }
}