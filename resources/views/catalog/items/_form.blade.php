{{-- Shared form partial for service / product create & edit --}}
<div class="space-y-6"
     x-data="{ itemType: '{{ old('type', $item->type ?? 'service') }}' }">

    {{-- Category + Type row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="catalog_category_id" class="block text-sm font-medium text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
            <select name="catalog_category_id" id="catalog_category_id"
                    class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm"
                    required>
                <option value="">— Select category —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ old('catalog_category_id', $item->catalog_category_id ?? '') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('catalog_category_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
            <div class="flex rounded-lg overflow-hidden border border-white/10">
                @foreach($types as $key => $label)
                    <label class="flex-1 text-center cursor-pointer">
                        <input type="radio" name="type" value="{{ $key }}"
                               x-model="itemType"
                               {{ old('type', $item->type ?? 'service') === $key ? 'checked' : '' }}
                               class="sr-only">
                        <span :class="itemType === '{{ $key }}'
                                        ? 'bg-cyan-600/20 text-cyan-300 border-cyan-600/40'
                                        : 'text-gray-400 hover:text-gray-200'"
                              class="block py-2 text-sm font-medium transition-colors border-r border-white/10 last:border-r-0">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="name"
               value="{{ old('name', $item->name ?? '') }}"
               class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm"
               placeholder="e.g. Spare Tire Swap, Jump Start, Lockout"
               required>
        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Description</label>
        <textarea name="description" id="description" rows="2"
                  class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">{{ old('description', $item->description ?? '') }}</textarea>
        @error('description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="base_cost" class="block text-sm font-medium text-gray-300 mb-1">Base Cost <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                <input type="number" name="base_cost" id="base_cost" step="0.01" min="0"
                       value="{{ old('base_cost', $item->base_cost ?? '') }}"
                       class="w-full pl-7 rounded-lg border-white/10 shadow-xs input-crystal text-sm"
                       required>
            </div>
            @error('base_cost') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="unit" class="block text-sm font-medium text-gray-300 mb-1">Unit <span class="text-red-500">*</span></label>
            <select name="unit" id="unit"
                    class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">
                @foreach($units as $key => $label)
                    <option value="{{ $key }}" {{ old('unit', $item->unit ?? 'each') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('unit') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="pricing_type" class="block text-sm font-medium text-gray-300 mb-1">Pricing Type <span class="text-red-500">*</span></label>
            <select name="pricing_type" id="pricing_type"
                    class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">
                @foreach($pricingTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('pricing_type', $item->pricing_type ?? 'fixed') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('pricing_type') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-300 mb-1">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order"
                   value="{{ old('sort_order', $item->sort_order ?? 0) }}"
                   class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm"
                   min="0" max="9999">
            @error('sort_order') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end pb-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       class="rounded-sm border-white/10 text-cyan-400 focus:ring-cyan-500"
                       {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-300">Active</span>
            </label>
        </div>
    </div>

    {{-- Inventory — only visible when type = product --}}
    <div x-show="itemType === 'product'" x-cloak class="border border-blue-500/20 bg-blue-500/5 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-blue-300 mb-4">Inventory Tracking</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex items-center gap-3">
                <input type="hidden" name="track_inventory" value="0">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="track_inventory" value="1"
                           id="track_inventory"
                           class="rounded-sm border-white/10 text-cyan-400 focus:ring-cyan-500"
                           {{ old('track_inventory', $item->track_inventory ?? false) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300">Track inventory for this product</span>
                </label>
            </div>
            <div>
                <label for="qty_on_hand" class="block text-sm font-medium text-gray-300 mb-1">Quantity on Hand</label>
                <input type="number" name="qty_on_hand" id="qty_on_hand" step="1" min="0"
                       value="{{ old('qty_on_hand', $item->qty_on_hand ?? 0) }}"
                       class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">
                @error('qty_on_hand') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        @if(isset($item) && $item && $item->track_inventory)
            <p class="text-xs text-gray-500 mt-3">Reserved: {{ number_format($item->qty_reserved, 0) }} &nbsp;|&nbsp; Available: {{ number_format($item->qty_available, 0) }}</p>
        @endif
    </div>

    {{-- Accounting Settings --}}
    <div class="border-t border-white/10 pt-6">
        <h3 class="text-sm font-semibold text-gray-300 mb-4">Accounting</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="revenue_account_id" class="block text-sm font-medium text-gray-300 mb-1">Revenue Account</label>
                <select name="revenue_account_id" id="revenue_account_id"
                        class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">
                    <option value="">Default (4000)</option>
                    @foreach ($revenueAccounts as $acct)
                        <option value="{{ $acct->id }}" {{ old('revenue_account_id', $item->revenue_account_id ?? '') == $acct->id ? 'selected' : '' }}>
                            {{ $acct->code }} {{ $acct->name }}
                        </option>
                    @endforeach
                </select>
                @error('revenue_account_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="cogs_account_id" class="block text-sm font-medium text-gray-300 mb-1">
                    COGS / Expense Account
                    <span x-show="itemType === 'product'" class="text-blue-400 text-xs font-normal ml-1">(recommended for products)</span>
                </label>
                <select name="cogs_account_id" id="cogs_account_id"
                        class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">
                    <option value="">None</option>
                    @foreach ($cogsAccounts as $acct)
                        <option value="{{ $acct->id }}" {{ old('cogs_account_id', $item->cogs_account_id ?? '') == $acct->id ? 'selected' : '' }}>
                            {{ $acct->code }} {{ $acct->name }}
                        </option>
                    @endforeach
                </select>
                @error('cogs_account_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="core_amount" class="block text-sm font-medium text-gray-300 mb-1">Core Deposit Amount</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                    <input type="number" name="core_amount" id="core_amount" step="0.01" min="0"
                           value="{{ old('core_amount', $item->core_amount ?? '') }}"
                           class="w-full pl-7 rounded-lg border-white/10 shadow-xs input-crystal text-sm">
                </div>
                @error('core_amount') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end gap-6 pb-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="core_required" value="0">
                    <input type="checkbox" name="core_required" value="1"
                           class="rounded-sm border-white/10 text-cyan-400 focus:ring-cyan-500"
                           {{ old('core_required', $item->core_required ?? false) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300">Core Required</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="taxable" value="0">
                    <input type="checkbox" name="taxable" value="1"
                           class="rounded-sm border-white/10 text-cyan-400 focus:ring-cyan-500"
                           {{ old('taxable', $item->taxable ?? true) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-300">Taxable</span>
                </label>
            </div>
        </div>
    </div>
</div>
