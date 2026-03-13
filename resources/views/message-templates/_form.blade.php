{{-- Shared form partial for create / edit --}}
<div class="space-y-6">
    {{-- Name & Slug --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Template Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name"
                   value="{{ old('name', $template->name ?? '') }}"
                   class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm"
                   required>
            @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-gray-300 mb-1">Slug</label>
            <input type="text" name="slug" id="slug"
                   value="{{ old('slug', $template->slug ?? '') }}"
                   class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm font-mono"
                   placeholder="Auto-generated from name">
            @error('slug') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Category & Sort --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="category" class="block text-sm font-medium text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
            <select name="category" id="category"
                    class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm">
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ old('category', $template->category ?? 'general') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('category') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-300 mb-1">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order"
                   value="{{ old('sort_order', $template->sort_order ?? 0) }}"
                   class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm"
                   min="0" max="9999">
            @error('sort_order') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end pb-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       class="rounded-sm border-white/10 text-cyan-400 focus:ring-cyan-500"
                       {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-300">Active</span>
            </label>
        </div>
    </div>

    {{-- Template Body --}}
    <div>
        <label for="body" class="block text-sm font-medium text-gray-300 mb-1">Message Body <span class="text-red-500">*</span></label>
        <textarea name="body" id="body" rows="5"
                  class="w-full rounded-lg border-white/10 shadow-xs input-crystal text-sm font-mono"
                  placeholder="Hi @{{ customer_first_name }}, your @{{ service_type }} service is confirmed..."
                  required>{{ old('body', $template->body ?? '') }}</textarea>
        <div class="flex items-center justify-between mt-1">
            <p class="text-xs text-gray-500">Use <code class="bg-white/5 px-1 rounded-sm">@{{ variable_name }}</code> for dynamic content. Max 1600 chars.</p>
            <span id="charCount" class="text-xs text-gray-400">0 / 1600</span>
        </div>
        @error('body') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Variable Reference --}}
    <div>
        <h3 class="text-sm font-medium text-gray-300 mb-2">Available Variables</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
            @foreach($availableVariables as $key => $def)
                <button type="button"
                        onclick="insertVariable('{{ $key }}')"
                        class="text-left p-2 bg-white/5 hover:bg-cyan-500/10 rounded-sm border border-white/10 hover:border-blue-300 transition-colors group">
                    <code class="text-xs text-cyan-400 font-mono block group-hover:text-cyan-300">@{{ {{ e($key) }} }}</code>
                    <span class="text-[10px] text-gray-500">{{ $def['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>

<script>
    // Character counter
    const bodyEl = document.getElementById('body');
    const charCountEl = document.getElementById('charCount');

    function updateCharCount() {
        const len = bodyEl.value.length;
        charCountEl.textContent = len + ' / 1600';
        charCountEl.classList.toggle('text-red-500', len > 1600);
        charCountEl.classList.toggle('text-gray-400', len <= 1600);
    }
    bodyEl.addEventListener('input', updateCharCount);
    updateCharCount();

    // Insert variable at cursor
    function insertVariable(varName) {
        const tag = '{{ ' + varName + ' }}';
        const start = bodyEl.selectionStart;
        const end = bodyEl.selectionEnd;
        bodyEl.value = bodyEl.value.substring(0, start) + tag + bodyEl.value.substring(end);
        bodyEl.selectionStart = bodyEl.selectionEnd = start + tag.length;
        bodyEl.focus();
        updateCharCount();
    }
</script>
