@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm text-gray-300">Service Request #{{ $serviceRequest->id }}</p>
                <h1 class="text-3xl font-semibold text-white">Create Estimate</h1>
                <p class="max-w-2xl text-sm text-gray-300">Build a polished estimate with a clean line items table, quick catalog selection, and responsive totals summary.</p>
            </div>
            <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center gap-2 rounded-full border border-cyan-500/40 bg-slate-900/95 px-5 py-3 text-sm font-semibold text-cyan-100 transition hover:border-cyan-300 hover:bg-slate-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Back to ticket
            </a>
        </div>

        @if($errors->any())
            <div class="rounded-3xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-200">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('estimates.store', $serviceRequest) }}" method="POST" @submit="prepareSubmit" x-data="estimateForm()" @catalog-item-selected.window="addCatalogItem($event.detail)">
            @csrf

            <div class="rounded-[2rem] border border-white/20 bg-slate-950/95 shadow-2xl shadow-cyan-500/10 overflow-hidden">
                <div class="grid gap-6 lg:grid-cols-[1.5fr_0.95fr] p-6 sm:p-8 border-b border-white/20 bg-slate-950/95">
                    <div class="space-y-5">
                        <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-cyan-500/10 text-cyan-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-cyan-300/70">Estimate</p>
                                    <p class="text-2xl font-semibold text-white">Draft estimate</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5">
                                <p class="text-xs uppercase tracking-[0.24em] text-gray-400">Client</p>
                                <p class="mt-3 text-sm text-gray-100 font-medium">
                                    @if($serviceRequest->customer)
                                        {{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}
                                    @else
                                        Unassigned customer
                                    @endif
                                </p>
                                @if($serviceRequest->customer?->phone)
                                    <p class="mt-2 text-sm text-gray-400">{{ $serviceRequest->customer->phone }}</p>
                                @endif
                                @if($serviceRequest->location)
                                    <p class="mt-3 text-sm text-gray-400">{{ $serviceRequest->location }}</p>
                                @endif
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5">
                                <p class="text-xs uppercase tracking-[0.24em] text-gray-500">Estimate details</p>
                                <div class="mt-3 space-y-3 text-sm text-gray-300">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-gray-400">Estimate number</span>
                                        <span class="font-medium">Auto-assigned</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-gray-400">Issue date</span>
                                        <span class="font-medium">{{ now()->format('M j, Y') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-gray-400">Expires</span>
                                        <span class="font-medium">{{ now()->addDays(30)->format('M j, Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-slate-900/80 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.24em] text-gray-500">Tax</p>
                                <p class="mt-3 text-sm text-gray-300">Use the state selector below to auto-populate rates when available.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div>
                                <label for="state_code" class="block text-sm font-medium text-white mb-1">State</label>
                                <select id="state_code" name="state_code" x-model="stateCode" @change="fetchTaxRate"
                                        class="w-full rounded-2xl border border-white/20 bg-slate-950/80 px-4 py-3 text-sm text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                                    <option value="">— Select State —</option>
                                    @foreach(\App\Models\StateTaxRate::stateList() as $code => $name)
                                        <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="tax_rate" class="block text-sm font-medium text-white mb-1">Tax rate (%)</label>
                                <input type="number" id="tax_rate" name="tax_rate" x-model.number="taxRate" step="0.0001" min="0" max="100"
                                       @input="recalculate"
                                       class="w-full rounded-2xl border border-white/20 bg-slate-950/80 px-4 py-3 text-sm text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40" />
                            </div>
                            <p class="text-xs text-green-400" x-show="taxRateFromDb" x-cloak>
                                Rate loaded from saved state tax rates.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 pb-8 pt-8 sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-white">Line items</h2>
                            <p class="mt-1 text-sm text-gray-300">Add products or services from your catalog or create a blank line item.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <x-add-item-modal :categories="$categories" />
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-white/20 bg-slate-900/80">
                        <template x-if="items.length === 0">
                            <div class="px-6 py-16 text-center text-gray-300">
                                <svg class="mx-auto mb-4 h-10 w-10 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                <p class="text-base font-medium text-white">No items yet</p>
                                <p class="mt-1 text-sm">Select an item from the catalog or add a blank line item to begin.</p>
                            </div>
                        </template>

                        <div x-show="items.length > 0" class="overflow-x-auto">
                            <table class="w-full border-separate border-spacing-0 text-sm">
                                <thead class="bg-slate-950/90 text-left text-xs uppercase tracking-[0.24em] text-gray-300 border-b border-white/20">
                                    <tr>
                                        <th class="px-5 py-4">Item</th>
                                        <th class="px-5 py-4 text-right">Unit price</th>
                                        <th class="px-5 py-4 text-center w-24">Qty</th>
                                        <th class="px-5 py-4 text-center w-28">Unit</th>
                                        <th class="px-5 py-4 text-right w-28">Amount</th>
                                        <th class="w-14"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="border-t border-white/20 even:bg-slate-950/80 hover:bg-slate-900/50 transition">
                                            <td class="px-5 py-4 align-top">
                                                <div class="space-y-2">
                                                    <input type="text" x-model="item.name" required placeholder="Item name"
                                                           class="w-full rounded-2xl border border-white/20 bg-slate-950/90 px-3 py-2 text-sm text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40" />
                                                    <input type="text" x-model="item.description" placeholder="Description (optional)"
                                                           class="w-full rounded-2xl border border-white/20 bg-slate-950/90 px-3 py-2 text-xs text-gray-300 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40" />
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <input type="number" x-model.number="item.unit_price" step="0.01" min="0" required @input="recalculate"
                                                       class="w-full rounded-2xl border border-white/20 bg-slate-950/90 px-3 py-2 text-sm text-right text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40" />
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <input type="number" x-model.number="item.quantity" step="0.01" min="0.01" required @input="recalculate"
                                                       class="w-full rounded-2xl border border-white/20 bg-slate-950/90 px-3 py-2 text-sm text-center text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40" />
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <select x-model="item.unit"
                                                        class="w-full rounded-2xl border border-white/20 bg-slate-950/90 px-3 py-2 text-sm text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                                                    <option value="each">Each</option>
                                                    <option value="mile">Mile</option>
                                                    <option value="hour">Hour</option>
                                                    <option value="gallon">Gallon</option>
                                                </select>
                                            </td>
                                            <td class="px-5 py-4 text-right align-top">
                                                <span class="text-sm font-semibold text-white" x-text="formatCurrency(item.unit_price * item.quantity)"></span>
                                            </td>
                                            <td class="px-5 py-4 text-center align-top">
                                                <button type="button" @click="removeItem(index)"
                                                        class="rounded-full p-2 text-gray-400 transition hover:bg-white/5 hover:text-red-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_0.95fr]">
                        <div>
                            <div class="rounded-3xl border border-white/20 bg-slate-900/80 p-5">
                                <label for="notes" class="block text-sm font-medium text-white mb-2">Notes</label>
                                <textarea id="notes" name="notes" rows="4" placeholder="Optional notes for this estimate..."
                                          class="w-full resize-none rounded-2xl border border-white/20 bg-slate-950/90 px-4 py-3 text-sm text-white focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/40">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-white/20 bg-slate-900/80 p-5">
                            <p class="text-sm uppercase tracking-[0.24em] text-gray-400 font-semibold">Totals</p>
                            <div class="mt-4 space-y-3 text-sm text-gray-300">
                                <div class="flex items-center justify-between">
                                    <span>Subtotal</span>
                                    <span class="font-medium" x-text="formatCurrency(subtotal)"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Tax</span>
                                    <span class="font-medium" x-text="formatCurrency(taxAmount)"></span>
                                </div>
                                <div class="border-t border-white/10 pt-4 flex items-center justify-between text-base font-semibold text-white">
                                    <span>Total</span>
                                    <span x-text="formatCurrency(total)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('service-requests.show', $serviceRequest) }}" class="text-sm text-gray-300 hover:text-cyan-300 transition">&larr; Cancel</a>
                <button type="submit" x-bind:disabled="items.length === 0"
                        class="inline-flex items-center justify-center rounded-full border border-cyan-500/30 bg-linear-to-r from-cyan-500 to-blue-500 px-8 py-3 text-base font-bold text-white transition hover:from-cyan-400 hover:to-blue-400 disabled:cursor-not-allowed disabled:opacity-50 shadow-xl shadow-cyan-500/20">
                    Create Estimate
                </button>
            </div>

            <div id="hidden-items-container"></div>
        </form>
    </div>
</div>

<script type="application/json" id="estimate-init-data">
{!! json_encode([
    'stateCode' => old('state_code', ''),
    'taxRate' => old('tax_rate', 0),
    'taxRateFromDb' => false,
    'items' => old('items', []),
]) !!}
</script>

<script>
function estimateForm() {
    const init = JSON.parse(document.getElementById('estimate-init-data').textContent);
    return {
        ...init,
        subtotal: 0,
        taxAmount: 0,
        total: 0,

        addCatalogItem(catalogItem) {
            this.items.push({
                catalog_item_id: catalogItem.catalog_item_id,
                name: catalogItem.name,
                description: catalogItem.description || '',
                unit_price: catalogItem.unit_price,
                quantity: catalogItem.quantity ?? 1,
                unit: catalogItem.unit || 'each',
            });
            this.recalculate();
        },

        removeItem(index) {
            if (!confirm('Remove this line item?')) {
                return;
            }
            this.items.splice(index, 1);
            this.recalculate();
        },

        recalculate() {
            this.subtotal = this.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0), 0);
            this.taxAmount = Math.round(this.subtotal * (parseFloat(this.taxRate) || 0) / 100 * 100) / 100;
            this.total = this.subtotal + this.taxAmount;
        },

        formatCurrency(amount) {
            return '$' + Number(amount || 0).toFixed(2);
        },

        async fetchTaxRate() {
            if (!this.stateCode) {
                this.taxRate = 0;
                this.taxRateFromDb = false;
                this.recalculate();
                return;
            }
            try {
                const resp = await fetch(`/api/state-tax-rate/${encodeURIComponent(this.stateCode)}`);
                const data = await resp.json();
                if (data.rate !== null && data.rate !== undefined) {
                    this.taxRate = parseFloat(data.rate);
                    this.taxRateFromDb = true;
                } else {
                    this.taxRate = 0;
                    this.taxRateFromDb = false;
                }
            } catch {
                this.taxRateFromDb = false;
            }
            this.recalculate();
        },

        prepareSubmit() {
            const container = document.getElementById('hidden-items-container');
            container.innerHTML = '';
            this.items.forEach((item, i) => {
                for (const [key, value] of Object.entries(item)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${i}][${key}]`;
                    input.value = value ?? '';
                    container.appendChild(input);
                }
            });
        },
    };
}
</script>
@endsection
