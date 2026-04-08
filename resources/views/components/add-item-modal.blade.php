@props(['categories'])

<div x-data="addItemModal()" class="relative">
    <button type="button" @click="open()"
            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-slate-900/70 px-4 py-2 text-sm font-semibold text-cyan-300 transition hover:border-cyan-500/40 hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-slate-950">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Add item
    </button>

    <div x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-6">
        <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm transition-opacity" @click="close()"></div>

        <div x-show="show"
             x-transition.enter="ease-out duration-300"
             x-transition.enter-start="opacity-0 translate-y-4"
             x-transition.enter-end="opacity-100 translate-y-0"
             x-transition.leave="ease-in duration-200"
             x-transition.leave-start="opacity-100 translate-y-0"
             x-transition.leave-end="opacity-0 translate-y-4"
             class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-3xl border border-white/10 bg-slate-950/95 shadow-2xl">
            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-6 py-5 bg-slate-900/95">
                <div>
                    <p class="text-sm font-semibold text-white">Add item to estimate</p>
                    <p class="text-sm text-gray-400">Search your catalog or create a blank item.</p>
                </div>
                <button type="button" @click="close()" class="rounded-full border border-white/10 bg-slate-900/80 p-2 text-gray-300 transition hover:border-white/20 hover:text-white">
                    <span class="sr-only">Close modal</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-6 py-5 sm:px-8 sm:py-6">
                <div class="mb-5">
                    <label for="add-item-search" class="sr-only">Search catalog</label>
                    <input id="add-item-search" x-ref="searchInput" x-model="search" type="search"
                           placeholder="Search catalog items"
                           class="w-full rounded-2xl border border-white/10 bg-slate-900/80 px-4 py-3 text-sm text-white placeholder:text-gray-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
                </div>

                <div class="space-y-5 max-h-[60vh] overflow-y-auto pr-1">
                    <template x-for="category in filteredCategories" :key="category.id">
                        <div class="rounded-3xl border border-white/10 bg-slate-950/90 p-4">
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-white/10">
                                <div>
                                    <p class="text-sm font-semibold text-white" x-text="category.name"></p>
                                    <p class="text-xs text-gray-500" x-text="category.items.length + ' item' + (category.items.length === 1 ? '' : 's')"></p>
                                </div>
                            </div>

                            <template x-for="item in category.items.filter(matches)" :key="item.id">
                                <div class="mt-3 rounded-2xl border border-white/10 bg-slate-900/70 p-4 hover:border-cyan-500/40 hover:bg-slate-900/90 transition">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-white" x-text="item.name"></p>
                                            <p class="mt-1 text-sm text-gray-400" x-text="item.description || 'No description'"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-white" x-text="formatCurrency(item.base_cost)"></p>
                                            <p class="text-xs text-gray-500" x-text="item.unit"></p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <button type="button" @click="select(item)"
                                                class="inline-flex items-center justify-center rounded-full bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">
                                            Add item
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="category.items.filter(matches).length === 0">
                                <p class="mt-3 text-sm text-gray-500">No matching items in this category.</p>
                            </template>
                        </div>
                    </template>

                    <template x-if="filteredCategories.length === 0">
                        <div class="rounded-3xl border border-white/10 bg-slate-950/90 p-6 text-center text-sm text-gray-400">
                            No catalog items match your search. Try a different keyword or create a custom line.
                        </div>
                    </template>
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" @click="addBlankItem()"
                            class="inline-flex items-center justify-center rounded-full border border-white/10 bg-slate-900/80 px-4 py-2 text-sm font-semibold text-gray-200 transition hover:border-cyan-500/40 hover:bg-slate-900/95">
                        Add blank line item
                    </button>
                    <p class="text-xs text-gray-500">Selected item will be added to the estimate and the modal will close.</p>
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
        search: '',
        categories: JSON.parse(document.getElementById('add-item-modal-data').textContent),

        open() {
            this.show = true;
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },

        close() {
            this.show = false;
            this.search = '';
        },

        select(item) {
            this.$dispatch('catalog-item-selected', {
                catalog_item_id: item.id,
                name: item.name,
                description: item.description || '',
                unit_price: item.base_cost,
                quantity: 1,
                unit: item.unit || 'each',
            });
            this.close();
        },

        addBlankItem() {
            this.$dispatch('catalog-item-selected', {
                catalog_item_id: null,
                name: '',
                description: '',
                unit_price: 0,
                quantity: 1,
                unit: 'each',
            });
            this.close();
        },

        matches(item) {
            const query = this.search.trim().toLowerCase();
            if (!query) return true;
            return item.name.toLowerCase().includes(query) || (item.description || '').toLowerCase().includes(query);
        },

        get filteredCategories() {
            return this.categories.map(category => ({
                ...category,
                items: category.items.filter(item => this.matches(item)),
            })).filter(category => category.items.length > 0);
        },

        formatCurrency(value) {
            return '$' + Number(value || 0).toFixed(2);
        },
    };
}
</script>
