{{--
  Edit Estimate — estimates.edit
  Preserved features: CSRF, @method('PUT'), Alpine estimateEditForm
  (state/tax/items/totals + fetchTaxRate + prepareSubmit), breadcrumb,
  status select, tax config, catalog picker + custom items, line items
  table with totals, notes, hidden items container, update + cancel.
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4" x-data="estimateEditForm()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('service-requests.index') }}" class="hover:text-cyan-400">All Service Requests</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="hover:text-cyan-400">SR #{{ $serviceRequest->id }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('estimates.show', [$serviceRequest, $estimate]) }}" class="hover:text-cyan-400">Estimate #{{ $estimate->id }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-300 font-medium">Edit</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-500/30 rounded-lg p-4">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('estimates.update', [$serviceRequest, $estimate]) }}" method="POST" @submit="prepareSubmit">
        @csrf
        @method('PUT')

        {{-- Header --}}
        <div class="bg-linear-to-r from-blue-50 to-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-white">Edit Estimate #{{ $estimate->id }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        For Service Request #{{ $serviceRequest->id }}
                        @if($serviceRequest->customer)
                            — {{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}
                        @endif
                    </p>
                </div>
                <select name="status"
                        class="border border-white/10 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide focus:ring-2 focus:ring-cyan-500 focus:border-blue-500">
                    @foreach(\App\Models\Estimate::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected($estimate->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Tax Configuration --}}
        <div class="surface-1 p-6 border border-white/10">
            <h2 class="text-lg font-semibold text-white mb-3">Tax Configuration</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="state_code" class="block text-sm font-medium text-gray-300 mb-1">State</label>
                    <select id="state_code" name="state_code" x-model="stateCode" @change="fetchTaxRate"
                            class="w-full border border-white/10 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-blue-500">
                        <option value="">— Select State —</option>
                        @foreach(\App\Models\StateTaxRate::stateList() as $code => $name)
                            <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-300 mb-1">Tax Rate (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" x-model.number="taxRate" step="0.0001" min="0" max="100"
                           class="w-full border border-white/10 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-blue-500"
                           @input="recalculate">
                    <p class="text-xs text-green-400 mt-1" x-show="taxRateFromDb" x-cloak>
                        <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Rate loaded from saved state tax rates
                    </p>
                </div>
            </div>
        </div>

        {{-- Add from Catalog --}}
        <div class="surface-1 p-6 border border-white/10" x-data="{ search: '' }">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-white">Add from Catalog</h2>
                <input type="text" x-model="search" placeholder="Search items..."
                       class="border border-white/10 rounded-md px-3 py-1.5 text-sm w-48 focus:ring-2 focus:ring-cyan-500 focus:border-blue-500">
            </div>

            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($categories as $category)
                    <details class="border border-white/10 rounded-md group">
                        <summary class="px-4 py-2.5 cursor-pointer text-sm font-medium text-gray-300 bg-white/5 hover:bg-cyan-500/10 flex items-center justify-between select-none">
                            <span>{{ $category->name }}</span>
                            <span class="text-xs text-gray-400">{{ $category->items->count() }} items</span>
                        </summary>
                        <div class="px-4 pb-3 divide-y divide-gray-100">
                            @foreach($category->items as $item)
                                <div class="flex items-center justify-between py-2"
                                     x-show="!search || {{ json_encode(strtolower($item->name)) }}.includes(search.toLowerCase())"
                                     x-cloak>
                                    <div class="min-w-0 mr-3">
                                        <span class="text-sm font-medium text-white">{{ $item->name }}</span>
                                        @if($item->description)
                                            <p class="text-xs text-gray-500 truncate">{{ Str::limit($item->description, 80) }}</p>
                                        @endif
                                        <span class="text-xs text-gray-400 font-mono">${{ number_format($item->base_cost, 2) }}/{{ $item->unit }}</span>
                                    </div>
                                    <button type="button"
                                            @click="addCatalogItem({{ $item->id }}, {{ json_encode($item->name) }}, {{ json_encode($item->description) }}, {{ $item->base_cost }}, {{ json_encode($item->unit) }})"
                                            class="shrink-0 text-cyan-400 hover:text-cyan-300 hover:bg-cyan-500/10 rounded-sm p-1.5 transition" title="Add to estimate">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>

            <div class="mt-3 pt-3 border-t border-white/10">
                <button type="button" @click="addCustomItem"
                        class="inline-flex items-center gap-1.5 text-sm text-cyan-400 hover:text-cyan-300 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add Custom Line Item
                </button>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="surface-1 overflow-hidden border border-white/10">
            <div class="px-6 py-4 border-b border-white/10 bg-white/5">
                <h2 class="text-lg font-semibold text-white">Estimate Items</h2>
            </div>

            <template x-if="items.length === 0">
                <div class="px-6 py-10 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <p class="text-sm text-gray-400">No items yet</p>
                    <p class="text-xs text-gray-400 mt-0.5">Use the catalog above or add a custom line item</p>
                </div>
            </template>

            <div x-show="items.length > 0">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 border-b border-white/10">
                        <tr>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Item</th>
                            <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wider w-28">Price</th>
                            <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wider w-20">Qty</th>
                            <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wider w-24">Unit</th>
                            <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-400 uppercase tracking-wider w-24">Amount</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-white/10 last:border-b-0 even:bg-white/5/60">
                                <td class="px-4 py-2.5">
                                    <input type="text" x-model="item.name" required placeholder="Item name"
                                           class="w-full border border-white/10 rounded-sm px-2 py-1 text-sm focus:ring-1 focus:ring-cyan-500 focus:border-blue-500">
                                    <input type="text" x-model="item.description" placeholder="Description (optional)"
                                           class="w-full border border-white/10 rounded-sm px-2 py-1 text-xs text-gray-500 mt-1 focus:ring-1 focus:ring-cyan-500 focus:border-blue-500">
                                </td>
                                <td class="px-3 py-2.5">
                                    <input type="number" x-model.number="item.unit_price" step="0.01" min="0" required @input="recalculate"
                                           class="w-full border border-white/10 rounded-sm px-2 py-1 text-sm text-right font-mono focus:ring-1 focus:ring-cyan-500 focus:border-blue-500">
                                </td>
                                <td class="px-3 py-2.5">
                                    <input type="number" x-model.number="item.quantity" step="0.01" min="0.01" required @input="recalculate"
                                           class="w-full border border-white/10 rounded-sm px-2 py-1 text-sm text-center focus:ring-1 focus:ring-cyan-500 focus:border-blue-500">
                                </td>
                                <td class="px-3 py-2.5">
                                    <select x-model="item.unit"
                                            class="w-full border border-white/10 rounded-sm px-2 py-1 text-sm focus:ring-1 focus:ring-cyan-500 focus:border-blue-500">
                                        <option value="each">Each</option>
                                        <option value="mile">Mile</option>
                                        <option value="hour">Hour</option>
                                        <option value="gallon">Gallon</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="text-sm font-semibold font-mono text-white" x-text="'$' + (item.unit_price * item.quantity).toFixed(2)"></span>
                                </td>
                                <td class="px-2 py-2.5 text-center">
                                    <button type="button" @click="removeItem(index)"
                                            class="text-gray-400 hover:text-red-500 transition" title="Remove item">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="border-t-2 border-white/10 bg-cyan-500/10/60 px-6 py-4" x-show="items.length > 0">
                <div class="flex flex-col items-end space-y-1.5 text-sm">
                    <div class="flex justify-between w-56">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-mono font-medium text-gray-300" x-text="'$' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between w-56">
                        <span class="text-gray-500">
                            Tax
                            <span class="text-gray-400" x-show="stateCode" x-text="'(' + stateCode + ' ' + taxRate + '%)'"></span>
                            <span class="text-gray-400" x-show="!stateCode && taxRate > 0" x-text="'(' + taxRate + '%)'"></span>
                        </span>
                        <span class="font-mono font-medium text-gray-300" x-text="'$' + taxAmount.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between w-56 border-t-2 border-cyan-500/30 pt-2 mt-1">
                        <span class="font-bold text-white">Total</span>
                        <span class="text-lg font-bold font-mono text-cyan-400" x-text="'$' + total.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="surface-1 p-6 border border-white/10">
            <label for="notes" class="block text-sm font-medium text-white mb-1">Notes</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Optional notes for this estimate..."
                      class="w-full border border-white/10 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-blue-500">{{ $estimate->notes }}</textarea>
        </div>

        {{-- Hidden items — populated on submit --}}
        <div id="hidden-items-container"></div>

        {{-- Actions --}}
        <div class="flex justify-between items-center">
            <a href="{{ route('estimates.show', [$serviceRequest, $estimate]) }}"
               class="text-sm text-gray-500 hover:text-cyan-400 underline">&larr; Cancel</a>
            <button type="submit" x-bind:disabled="items.length === 0"
                    class="bg-blue-600 text-white font-medium px-6 py-2.5 rounded-md  shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed">
                Update Estimate
            </button>
        </div>
    </form>
</div>

<script>
function estimateEditForm() {
    return {
        stateCode: @json($estimate->state_code ?? ''),
        taxRate: @json((float) $estimate->tax_rate),
        taxRateFromDb: false,
        items: @json($estimate->items->map(fn ($i) => [
            'catalog_item_id' => $i->catalog_item_id,
            'name' => $i->name,
            'description' => $i->description ?? '',
            'unit_price' => (float) $i->unit_price,
            'quantity' => (float) $i->quantity,
            'unit' => $i->unit,
        ])->values()),
        subtotal: @json((float) $estimate->subtotal),
        taxAmount: @json((float) $estimate->tax_amount),
        total: @json((float) $estimate->total),

        addCatalogItem(catalogItemId, name, description, unitPrice, unit) {
            this.items.push({
                catalog_item_id: catalogItemId,
                name: name,
                description: description || '',
                unit_price: unitPrice,
                quantity: 1,
                unit: unit,
            });
            this.recalculate();
        },

        addCustomItem() {
            this.items.push({
                catalog_item_id: null,
                name: '',
                description: '',
                unit_price: 0,
                quantity: 1,
                unit: 'each',
            });
        },

        removeItem(index) {
            this.items.splice(index, 1);
            this.recalculate();
        },

        recalculate() {
            this.subtotal = this.items.reduce((sum, item) => {
                return sum + (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0);
            }, 0);
            this.taxAmount = Math.round(this.subtotal * (parseFloat(this.taxRate) || 0) / 100 * 100) / 100;
            this.total = this.subtotal + this.taxAmount;
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

        prepareSubmit(e) {
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
