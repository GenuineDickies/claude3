<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use App\Services\Access\AuditLogger;
use App\Services\Access\PageRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Maintains the persisted page registry used by role-based page access.
 */
class PageController extends Controller
{
    public function __construct(
        private readonly PageRegistryService $pageRegistry,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * Show the page registry with optional name/path filtering.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));

        $pages = Page::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('page_name', 'like', "%{$search}%")
                        ->orWhere('page_path', 'like', "%{$search}%");
                });
            })
            ->orderBy('page_name')
            ->paginate(100)
            ->withQueryString();

        return view('admin.pages.index', [
            'pages' => $pages,
            'search' => $search,
        ]);
    }

    /**
     * Register a page manually and audit-log the creation.
     */
    public function store(StorePageRequest $request): RedirectResponse
    {
        $page = Page::create($request->validated());

        $this->auditLogger->log('page_created', $request->user(), [
            'page_id' => $page->id,
            'page_path' => $page->page_path,
        ], $request);

        return back()->with('success', 'Page registered successfully.');
    }

    /**
     * Update a registered page's metadata.
     */
    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->validated());

        $this->auditLogger->log('page_updated', $request->user(), [
            'page_id' => $page->id,
            'page_path' => $page->page_path,
        ], $request);

        return back()->with('success', 'Page updated successfully.');
    }

    /**
     * Remove a page from the registry after clearing role assignments.
     */
    public function destroy(Request $request, Page $page): RedirectResponse
    {
        $pagePath = $page->page_path;
        $page->roles()->detach();
        $page->delete();

        $this->auditLogger->log('page_deleted', $request->user(), [
            'page_path' => $pagePath,
        ], $request);

        return back()->with('success', 'Page deleted successfully.');
    }

    /**
     * Discover protected routes and sync them into the page registry.
     */
    public function sync(Request $request): RedirectResponse
    {
        $pages = $this->pageRegistry->sync();

        $this->auditLogger->log('pages_synced', $request->user(), [
            'count' => $pages->count(),
        ], $request);

        return back()->with('success', 'Registered pages were synced from the route list.');
    }
}