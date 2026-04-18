@props(['categories'])

<div x-data="addItemModal()" class="relative">
    <button type="button" @click="open()"
            class="inline-flex items-center gap-2 rounded-full bg-linear-to-r from-cyan-500 to-blue-500 px-5 py-3 text-sm font-bold text-white transition hover:from-cyan-400 hover:to-blue-400 focus:outline-none focus:ring-2 focus:ring-cyan-400 shadow-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Add item
    </button>

    <div x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-6">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md" @click="close()"></div>

        <div x-show="show"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-3xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/60">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-6 py-4">
                <p class="text-base font-bold text-white">Add item to estimate</p>
                <button type="button" @click.stop="close()"
                        class="rounded-full border border-white/10 bg-white/5 p-2 text-gray-400 transition hover:border-white/20 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-white/10 px-6">
                <button type="button" @click="tab = 'catalog'"
                        class="px-4 py-3 text-sm font-semibold transition border-b-2"
                        :class="tab === 'catalog' ? 'border-cyan-400 text-cyan-300' : 'border-transparent text-gray-400 hover:text-white'">
                    From catalog
                </button>
                <button type="button" @click="tab = 'custom'"
                        class="px-4 py-3 text-sm font-semibold transition border-b-2"
                        :class="tab === 'custom' ? 'border-cyan-400 text-cyan-300' : 'border-transparent text-gray-400 hover:text-white'">
                    Custom item
                </button>
            </div>

            {{-- Catalog tab --}}
            <div x-show="tab === 'catalog'" class="px-6 py-5">
                <input x-ref="searchInput" x-model="search" type="search"
                       placeholder="Search catalog items..."
                       class="mb-4 w-full rounded-2xl border border-slate-600/60 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />

                <div class="space-y-4 max-h-[50vh] overflow-y-auto pr-1">
                    <template x-for="category in filteredCategories" :key="category.id">
                        <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-4">
                            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-cyan-400" x-text="category.name"></p>
                            <template x-for="item in category.items" :key="item.id">
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-white/5 bg-slate-950/40 px-4 py-3 mb-2 last:mb-0 hover:border-cyan-500/30 hover:bg-slate-800/60 transition">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-white" x-text="item.name"></p>
                                        <p class="text-xs text-gray-400 mt-0.5" x-text="item.description || ''"></p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-cyan-300" x-text="formatCurrency(item.base_cost)"></p>
                                            <p class="text-xs text-gray-500" x-text="'per ' + item.unit"></p>
                                        </div>
                                        <button type="button" @click.stop="select(item)"
                                                class="rounded-full bg-cyan-500/15 border border-cyan-500/30 px-3 py-1.5 text-xs font-bold text-cyan-300 transition hover:bg-cyan-500 hover:text-white hover:border-cyan-500">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="filteredCategories.length === 0">
                        <p class="py-8 text-center text-sm text-gray-500">No items match your search.</p>
                    </template>
                </div>
            </div>

            {{-- Custom item tab --}}
            <div x-show="tab === 'custom'" class="px-6 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">Item name <span class="text-red-400">*</span></label>
                        <input x-model="custom.name" type="text" placeholder="e.g. Service call, Materials"
                               class="w-full rounded-2xl border border-slate-600/60 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">Description</label>
                        <input x-model="custom.description" type="text" placeholder="Optional"
                               class="w-full rounded-2xl border border-slate-600/60 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">Unit price ($)</label>
                        <input x-model.number="custom.unit_price" type="number" step="0.01" min="0" placeholder="0.00"
                               class="w-full rounded-2xl border border-slate-600/60 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">Quantity</label>
                        <input x-model.number="custom.quantity" type="number" step="0.01" min="0.01" placeholder="1"
                               class="w-full rounded-2xl border border-slate-600/60 bg-slate-950/60 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">Unit</label>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" @keydown.escape="open = false"
                                    class="w-full rounded-2xl border border-slate-600/60 bg-slate-950/60 px-4 py-3 text-sm text-white text-left flex items-center justify-between focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                                <span x-text="custom.unit.charAt(0).toUpperCase() + custom.unit.slice(1)"></span>
                                <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak @click.outside="open = false"
                                 class="absolute z-50 mt-1 w-full rounded-2xl border border-slate-600/60 bg-slate-950 shadow-xl shadow-black/60 py-1">
                                <template x-for="opt in ['each','mile','hour','gallon']" :key="opt">
                                    <button type="button" @click="custom.unit = opt; open = false"
                                            class="w-full px-4 py-2 text-sm text-left text-white hover:bg-slate-800 hover:text-cyan-300 transition capitalize"
                                            :class="custom.unit === opt && 'bg-slate-800 text-cyan-300'"
                                            x-text="opt.charAt(0).toUpperCase() + opt.slice(1)"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-500" x-text="custom.unit_price && custom.quantity ? 'Total: ' + formatCurrency(custom.unit_price * custom.quantity) : ''"></p>
                    <button type="button" @click.stop="addCustomItem()"
                            :disabled="!custom.name.trim()"
                            class="inline-flex items-center gap-2 rounded-full bg-linear-to-r from-cyan-500 to-blue-500 px-6 py-2.5 text-sm font-bold text-white transition hover:from-cyan-400 hover:to-blue-400 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg shadow-cyan-500/20">
                        Add to estimate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="add-item-modal-data">
{!! json_encode($categories->map(fn ($category) => [
    'id' => $category->id,
    'name' => $category->name,
    'items' => $category->items->map(fn ($item) => [
        'id' => $item->id,
        'name' => $item->name,
        'description' => $item->description,
        'base_cost' => (float) $item->base_cost,
        'unit' => $item->unit,
    ])->values(),
])->values()) !!}
</script>

<script>
function addItemModal() {
    return {
        show: false,
        tab: 'catalog',
        search: '',
        categories: JSON.parse(document.getElementById('add-item-modal-data').textContent),
        custom: { name: '', description: '', unit_price: 0, quantity: 1, unit: 'each' },

        open() {
            this.show = true;
            this.tab = 'catalog';
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },

        close() {
            this.show = false;
            this.search = '';
            this.custom = { name: '', description: '', unit_price: 0, quantity: 1, unit: 'each' };
        },

        emit(detail) {
            window.dispatchEvent(new CustomEvent('catalog-item-selected', { detail, bubbles: true }));
        },

        select(item) {
            this.emit({
                catalog_item_id: item.id,
                name: item.name,
                description: item.description || '',
                unit_price: item.base_cost,
                quantity: 1,
                unit: item.unit || 'each',
            });
            this.close();
        },

        addCustomItem() {
            if (!this.custom.name.trim()) return;
            this.emit({
                catalog_item_id: null,
                name: this.custom.name.trim(),
                description: this.custom.description.trim(),
                unit_price: parseFloat(this.custom.unit_price) || 0,
                quantity: parseFloat(this.custom.quantity) || 1,
                unit: this.custom.unit,
            });
            this.close();
        },

        matches(item) {
            const q = this.search.trim().toLowerCase();
            if (!q) return true;
            return item.name.toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q);
        },

        get filteredCategories() {
            return this.categories.map(cat => ({
                ...cat,
                items: cat.items.filter(item => this.matches(item)),
            })).filter(cat => cat.items.length > 0);
        },

        formatCurrency(v) {
            return '$' + Number(v || 0).toFixed(2);
        },
    };
}
</script>
