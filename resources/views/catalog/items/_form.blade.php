{{-- Shared form partial for catalog item create / edit --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name"
                   value="{{ old('name', $item->name ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm"
                   required>
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
            <input type="text" name="sku" id="sku"
                   value="{{ old('sku', $item->sku ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm font-mono"
                   placeholder="Optional">
            @error('sku') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" id="description" rows="2"
                  class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('description', $item->description ?? '') }}</textarea>
        @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-1">Unit Price <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                <input type="number" name="unit_price" id="unit_price" step="0.01" min="0"
                       value="{{ old('unit_price', $item->unit_price ?? '') }}"
                       class="w-full pl-7 rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm"
                       required>
            </div>
            @error('unit_price') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit <span class="text-red-500">*</span></label>
            <select name="unit" id="unit"
                    class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm">
                @foreach($units as $key => $label)
                    <option value="{{ $key }}" {{ old('unit', $item->unit ?? 'each') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('unit') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="pricing_type" class="block text-sm font-medium text-gray-700 mb-1">Pricing Type <span class="text-red-500">*</span></label>
            <select name="pricing_type" id="pricing_type"
                    class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm">
                @foreach($pricingTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('pricing_type', $item->pricing_type ?? 'fixed') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('pricing_type') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order"
                   value="{{ old('sort_order', $item->sort_order ?? 0) }}"
                   class="w-full rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm"
                   min="0" max="9999">
            @error('sort_order') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end pb-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500"
                       {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Active</span>
            </label>
        </div>
    </div>
</div>
