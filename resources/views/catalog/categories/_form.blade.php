{{-- Shared form partial for service category create / edit --}}
<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Category Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="name"
               value="{{ old('name', $category->name ?? '') }}"
               class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm"
               placeholder="e.g. Tire Services, Battery Services, Towing"
               required>
        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-300 mb-1">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order"
                   value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                   class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm"
                   min="0" max="9999">
            @error('sort_order') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end pb-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       class="rounded-sm border-white/10 text-cyan-400 focus:ring-cyan-500"
                       {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-300">Active</span>
            </label>
        </div>
    </div>
</div>
