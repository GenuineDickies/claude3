<?php

namespace App\Services\Access;

use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class PageRegistryService
{
    public function __construct(private readonly PageAccessResolver $resolver)
    {
    }

    /** @return Collection<int, Page> */
    public function sync(): Collection
    {
        $syncedPages = collect();
        $seen = [];

        foreach (Route::getRoutes() as $route) {
            $path = $this->resolver->resolve($route);

            if ($path === null || isset($seen[$path])) {
                continue;
            }

            $seen[$path] = true;

            $page = Page::query()->firstOrNew(['page_path' => $path]);

            if (! $page->exists || blank($page->page_name)) {
                $page->page_name = $this->resolver->labelForPath($path);
            }

            $page->save();
            $syncedPages->push($page);
        }

        return $syncedPages->sortBy('page_name')->values();
    }
}