<?php

namespace App\Http\Controllers;

use App\Models\Account;
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
        return view('catalog.categories.create');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'integer|min:0|max:9999',
            'is_active'   => 'boolean',
        ]);

        CatalogCategory::create($validated);

        return redirect()->route('catalog.index')
            ->with('success', 'Service category "' . $validated['name'] . '" created.');
    }

    public function editCategory(CatalogCategory $category): View
    {
        return view('catalog.categories.edit', compact('category'));
    }

    public function updateCategory(Request $request, CatalogCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'integer|min:0|max:9999',
            'is_active'   => 'boolean',
        ]);

        $category->update($validated);

        return redirect()->route('catalog.index')
            ->with('success', 'Service category "' . $validated['name'] . '" updated.');
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
        $revenueAccounts = Account::general()->where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $cogsAccounts = Account::general()->whereIn('type', ['cogs', 'expense'])->where('is_active', true)->orderBy('code')->get();
        return view('catalog.items.create', compact('category', 'pricingTypes', 'units', 'revenueAccounts', 'cogsAccounts'));
    }

    public function storeItem(Request $request, CatalogCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'base_cost'          => 'required|numeric|min:0|max:99999999.99',
            'unit'               => ['required', 'string', Rule::in(array_keys(CatalogItem::units()))],
            'pricing_type'       => ['required', 'string', Rule::in(array_keys(CatalogItem::pricingTypes()))],
            'sort_order'         => 'integer|min:0|max:9999',
            'is_active'          => 'boolean',
            'revenue_account_id' => 'nullable|exists:accounts,id',
            'cogs_account_id'    => 'nullable|exists:accounts,id',
            'core_required'      => 'boolean',
            'core_amount'        => 'nullable|numeric|min:0|max:99999.99',
            'taxable'            => 'boolean',
        ]);

        $category->items()->create($validated);

        return redirect()->route('catalog.categories.show', $category)
            ->with('success', 'Service "' . $validated['name'] . '" created.');
    }

    public function editItem(CatalogCategory $category, CatalogItem $item): View
    {
        $pricingTypes = CatalogItem::pricingTypes();
        $units = CatalogItem::units();
        $revenueAccounts = Account::general()->where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $cogsAccounts = Account::general()->whereIn('type', ['cogs', 'expense'])->where('is_active', true)->orderBy('code')->get();
        return view('catalog.items.edit', compact('category', 'item', 'pricingTypes', 'units', 'revenueAccounts', 'cogsAccounts'));
    }

    public function updateItem(Request $request, CatalogCategory $category, CatalogItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'base_cost'          => 'required|numeric|min:0|max:99999999.99',
            'unit'               => ['required', 'string', Rule::in(array_keys(CatalogItem::units()))],
            'pricing_type'       => ['required', 'string', Rule::in(array_keys(CatalogItem::pricingTypes()))],
            'sort_order'         => 'integer|min:0|max:9999',
            'is_active'          => 'boolean',
            'revenue_account_id' => 'nullable|exists:accounts,id',
            'cogs_account_id'    => 'nullable|exists:accounts,id',
            'core_required'      => 'boolean',
            'core_amount'        => 'nullable|numeric|min:0|max:99999.99',
            'taxable'            => 'boolean',
        ]);

        $item->update($validated);

        return redirect()->route('catalog.categories.show', $category)
            ->with('success', 'Service "' . $validated['name'] . '" updated.');
    }

    public function destroyItem(CatalogCategory $category, CatalogItem $item): RedirectResponse
    {
        $name = $item->name;
        $item->delete();

        return redirect()->route('catalog.categories.show', $category)
            ->with('success', 'Service "' . $name . '" deleted.');
    }
}
