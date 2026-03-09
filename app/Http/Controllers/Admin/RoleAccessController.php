<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRoleAccessRequest;
use App\Models\Page;
use App\Models\Role;
use App\Services\Access\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleAccessController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function edit(Request $request, Role $role): View
    {
        $search = trim((string) $request->string('search'));

        $pages = Page::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('page_name', 'like', "%{$search}%")
                        ->orWhere('page_path', 'like', "%{$search}%");
                });
            })
            ->orderBy('page_name')
            ->get();

        return view('admin.access.edit', [
            'role' => $role->load('pages'),
            'pages' => $pages,
            'assignedPageIds' => $role->isAdministrator() ? $pages->pluck('id')->all() : $role->pages->pluck('id')->all(),
            'search' => $search,
        ]);
    }

    public function update(UpdateRoleAccessRequest $request, Role $role): RedirectResponse
    {
        if ($role->isAdministrator()) {
            return back()->withErrors([
                'page_ids' => 'The Administrator role always has access to all pages.',
            ]);
        }

        $pageIds = array_map('intval', $request->validated('page_ids', []));
        $role->pages()->sync($pageIds);

        $this->auditLogger->log('role_access_updated', $request->user(), [
            'role_id' => $role->id,
            'page_ids' => $pageIds,
        ], $request);

        return back()->with('success', 'Role access updated successfully.');
    }
}