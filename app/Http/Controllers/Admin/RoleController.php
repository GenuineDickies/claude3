<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use App\Services\Access\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles CRUD operations for business roles used by the RBAC system.
 */
class RoleController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    /**
     * Show the role list with search support and usage counts.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));

        $roles = Role::query()
            ->withCount(['users', 'pages'])
            ->when($search !== '', fn ($query) => $query->where('role_name', 'like', "%{$search}%"))
            ->orderBy('role_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
            'search' => $search,
        ]);
    }

    /**
     * Create a role and audit-log the change.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create($request->validated());

        $this->auditLogger->log('role_created', $request->user(), [
            'role_id' => $role->id,
            'role_name' => $role->role_name,
        ], $request);

        return back()->with('success', 'Role created successfully.');
    }

    /**
     * Update an existing role definition.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());

        $this->auditLogger->log('role_updated', $request->user(), [
            'role_id' => $role->id,
            'role_name' => $role->role_name,
        ], $request);

        return back()->with('success', 'Role updated successfully.');
    }

    /**
     * Delete a non-reserved, unassigned role after detaching page access.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->isAdministrator()) {
            return back()->withErrors([
                'role' => 'The Administrator role is reserved and cannot be deleted.',
            ]);
        }

        if ($role->users()->exists()) {
            return back()->withErrors([
                'role' => 'Roles that are still assigned to users cannot be deleted.',
            ]);
        }

        $roleName = $role->role_name;
        $role->pages()->detach();
        $role->delete();

        $this->auditLogger->log('role_deleted', $request->user(), [
            'role_name' => $roleName,
        ], $request);

        return back()->with('success', 'Role deleted successfully.');
    }
}