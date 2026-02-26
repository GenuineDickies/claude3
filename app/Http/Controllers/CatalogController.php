<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        $categories = CatalogCategory::withCount('items')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('catalog.index', compact('categories'));
    }

    // ── Categories ──────────────────────────────────────

    public function createCategory(): View
    {
        $types = CatalogCategory::types();
        return view('catalog.categories.create', compact('types'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => ['required', 'string', Rule::in(array_keys(CatalogCategory::types()))],
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'integer|min:0|max:9999',
            'is_active'   => 'boolean',
        ]);

        CatalogCategory::create($validated);

        return redirect()->route('catalog.index')
            ->with('success', 'Category "' . $validated['name'] . '" created.');
    }

    public function editCategory(CatalogCategory $category): View
    {
        $types = CatalogCategory::types();
        return view('catalog.categories.edit', compact('category', 'types'));
    }

    public function updateCategory(Request $request, CatalogCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => ['required', 'string', Rule::in(array_keys(CatalogCategory::types()))],
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'integer|min:0|max:9999',
            'is_active'   => 'boolean',
        ]);

        $category->update($validated);

        return redirect()->route('catalog.index')
            ->with('success', 'Category "' . $validated['name'] . '" updated.');
    }

    public function destroyCategory(CatalogCategory $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();

        return redirect()->route('catalog.index')
            ->with('success', 'Category "' . $name . '" deleted.');
    }

    // ── Items ───────────────────────────────────────────

    public function showCategory(CatalogCategory $category): View
    {
        $category->load(['items' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')]);
        return view('catalog.categories.show', compact('category'));
    }

    public function createItem(CatalogCategory $category): View
    {
        $pricingTypes = CatalogItem::pricingTypes();
        $units = CatalogItem::units();
        return view('catalog.items.create', compact('category', 'pricingTypes', 'units'));
    }

    public function storeItem(Request $request, CatalogCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'sku'          => 'nullable|string|max:100|unique:catalog_items,sku',
            'description'  => 'nullable|string|max:1000',
            'unit_price'   => 'required|numeric|min:0|max:99999999.99',
            'unit'         => ['required', 'string', Rule::in(array_keys(CatalogItem::units()))],
            'pricing_type' => ['required', 'string', Rule::in(array_keys(CatalogItem::pricingTypes()))],
            'sort_order'   => 'integer|min:0|max:9999',
            'is_active'    => 'boolean',
        ]);

        $category->items()->create($validated);

        return redirect()->route('catalog.categories.show', $category)
            ->with('success', 'Item "' . $validated['name'] . '" created.');
    }

    public function editItem(CatalogCategory $category, CatalogItem $item): View
    {
        $pricingTypes = CatalogItem::pricingTypes();
        $units = CatalogItem::units();
        return view('catalog.items.edit', compact('category', 'item', 'pricingTypes', 'units'));
    }

    public function updateItem(Request $request, CatalogCategory $category, CatalogItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'sku'          => ['nullable', 'string', 'max:100', Rule::unique('catalog_items', 'sku')->ignore($item->id)],
            'description'  => 'nullable|string|max:1000',
            'unit_price'   => 'required|numeric|min:0|max:99999999.99',
            'unit'         => ['required', 'string', Rule::in(array_keys(CatalogItem::units()))],
            'pricing_type' => ['required', 'string', Rule::in(array_keys(CatalogItem::pricingTypes()))],
            'sort_order'   => 'integer|min:0|max:9999',
            'is_active'    => 'boolean',
        ]);

        $item->update($validated);

        return redirect()->route('catalog.categories.show', $category)
            ->with('success', 'Item "' . $validated['name'] . '" updated.');
    }

    public function destroyItem(CatalogCategory $category, CatalogItem $item): RedirectResponse
    {
        $name = $item->name;
        $item->delete();

        return redirect()->route('catalog.categories.show', $category)
            ->with('success', 'Item "' . $name . '" deleted.');
    }
}
