{{--
  Create Estimate — estimates.create
  Preserved features: CSRF, Alpine estimateForm (state/tax/items/deposit/
  send-for-approval/totals + fetchTaxRate + prepareSubmit), breadcrumb,
  document header, tax config (auto-detect), catalog picker + custom items,
  line items table with totals, deposit toggle, notes, hidden items
  container, send-for-approval checkbox, submit + back actions.
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4" x-data="estimateForm(@js($initialItems ?? []))" x-init="init()">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('service-requests.index') }}" class="hover:text-cyan-400">All Service Requests</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="hover:text-cyan-400">SR #{{ $serviceRequest->id }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-300 font-medium">Create Estimate</span>
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

    {{-- Document Header --}}
    <div class="rounded-xl border border-white/10 overflow-hidden">
        <div class="bg-blue-900/30 border-b border-blue-500/20 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-sm font-bold uppercase tracking-widest text-blue-300">Estimate</h1>
                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wide">Draft</span>
            </div>
            <span class="text-xs text-white/40">{{ now()->format('F j, Y') }}</span>
        </div>
        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5 bg-white/[0.02]">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-white/30 mb-2">Prepared For</p>
                @if($serviceRequest->customer)
                    <p class="text-base font-bold text-white">{{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}</p>
                    @if($serviceRequest->customer->phone)
                        <p class="text-sm text-white/50 mt-0.5">{{ $serviceRequest->customer->phone }}</p>
                    @endif
                @else
                    <p class="text-sm text-white/40 italic">No customer on record</p>
                @endif
                @if($serviceRequest->location)
                    <p class="text-xs text-white/40 mt-2 flex items-start gap-1">
                        <svg class="w-3 h-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $serviceRequest->location }}
                    </p>
                @endif
            </div>
            <div class="sm:border-l sm:border-white/10 sm:pl-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-white/30 mb-2">Service Details</p>
                @if($serviceRequest->catalogItem)
                    <p class="text-base font-semibold text-white">{{ $serviceRequest->catalogItem->name }}</p>
                @else
                    <p class="text-sm text-white/40 italic">No service type selected</p>
                @endif
                @php
                    $vehicleParts = array_filter([
                        $serviceRequest->vehicle_year,
                        $serviceRequest->vehicle_make,
                        $serviceRequest->vehicle_model,
                        $serviceRequest->vehicle_color ? '(' . $serviceRequest->vehicle_color . ')' : null,
                    ]);
                @endphp
                @if(!empty($vehicleParts))
                    <p class="text-sm text-white/50 mt-0.5">{{ implode(' ', $vehicleParts) }}</p>
                @endif
                <p class="text-xs text-white/30 mt-2">Service Request #{{ $serviceRequest->id }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('estimates.store', $serviceRequest) }}" method="POST" @submit="prepareSubmit">
        @csrf

        {{-- Tax Configuration (collapsed when auto-detected) --}}
        <details class="surface-1 border border-white/10 group" @if(!$stateAutoDetected) open @endif>
            <summary class="px-6 py-3 cursor-pointer select-none list-none flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="text-sm font-medium text-gray-300">Tax Configuration</span>
                    @if($stateAutoDetected)
                        <span class="inline-flex items-center gap-1 text-xs text-green-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Auto-detected
                        </span>
                    @endif
                </div>
                <svg class="w-4 h-4 text-gray-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="px-6 pb-5 pt-3 border-t border-white/10">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="state_code" class="block text-sm font-medium text-gray-300 mb-1">State</label>
                        <select id="state_code" name="state_code" x-model="stateCode" @change="fetchTaxRate"
                                class="w-full border border-white/10 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-blue-500">
                            <option value="">— Select State —</option>
                            @foreach(\App\Models\StateTaxRate::stateList() as $code => $name)
                                <option value="{{ $code }}" @selected($stateCode === $code)>{{ $name }} ({{ $code }})</option>
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
                        <p class="text-xs text-gray-400 mt-1" x-show="!taxRateFromDb && stateCode" x-cloak>No saved rate — enter manually</p>
                    </div>
                </div>
            </div>
        </details>

        {{-- Add Items (collapsed by default) --}}
        <div class="surface-1 p-6 border border-white/10 space-y-3">
            <details x-data="{ search: '' }" class="border border-white/10 rounded-md group">
                <summary class="px-4 py-2.5 cursor-pointer text-sm font-medium text-gray-300 bg-white/5 hover:bg-cyan-500/10 flex items-center justify-between select-none">
                    <span>Add from Catalog</span>
                    <span class="text-xs text-gray-400">Click to expand</span>
                </summary>

                <div class="px-4 py-3 space-y-3 border-t border-white/10">
                    <div class="flex justify-end">
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
                </div>
            </details>

            <details class="border border-white/10 rounded-md group">
                <summary class="px-4 py-2.5 cursor-pointer text-sm font-medium text-gray-300 bg-white/5 hover:bg-cyan-500/10 flex items-center justify-between select-none">
                    <span>Add Custom Item</span>
                    <span class="text-xs text-gray-400">Click to expand</span>
                </summary>

                <div class="px-4 py-3 border-t border-white/10">
                    <button type="button" @click="addCustomItem"
                            class="inline-flex items-center gap-1.5 text-sm text-cyan-400 hover:text-cyan-300 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Add Custom Line Item
                    </button>
                </div>
            </details>

            <p class="text-xs text-gray-400" x-show="hasPrefill" x-cloak>
                Started with the selected service and quoted price from this service request.
            </p>
        </div>

        {{-- Line Items --}}
        <div class="surface-1 overflow-hidden border border-white/10">
            <div class="px-6 py-4 border-b border-white/10 bg-white/5">
                <h2 class="text-lg font-semibold text-white">Estimate Items</h2>
            </div>

            <template x-if="items.length === 0">
                <div class="px-6 py-10 text-center">
                    <svg class="mx-auto w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    <p class="text-sm text-gray-400">No items added yet</p>
                    <p class="text-xs text-gray-400 mt-0.5">Use the catalog above or add a custom line item</p>
                </div>
            </template>

            {{-- Table --}}
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

            {{-- Totals row —  inside the items card --}}
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

        {{-- Deposit Requirement --}}
        <div class="surface-1 p-6 border border-white/10">
            <h2 class="text-lg font-semibold text-white mb-3">Deposit Requirement</h2>
            <label class="inline-flex items-center gap-2 text-sm text-gray-300">
                <input type="checkbox" x-model="depositRequired" class="rounded border-white/20 text-cyan-500 focus:ring-cyan-500">
                Require a deposit before work starts
            </label>

            <div class="mt-3 max-w-xs" x-show="depositRequired" x-cloak>
                <label for="deposit_amount" class="block text-sm font-medium text-gray-300 mb-1">Required Deposit ($)</label>
                <input type="number" id="deposit_amount" name="deposit_amount" x-model.number="depositAmount" step="0.01" min="0"
                       class="w-full border border-white/10 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-blue-500"
                       @input="recalculate">
                <p class="text-xs text-gray-400 mt-1">Set the amount to collect up front.</p>
            </div>

            <input type="hidden" name="deposit_required" :value="depositRequired ? 1 : 0">
        </div>

        {{-- Notes --}}
        <div class="surface-1 p-6 border border-white/10">
            <label for="notes" class="block text-sm font-medium text-white mb-1">Notes</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Optional notes for this estimate..."
                      class="w-full border border-white/10 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-blue-500"></textarea>
        </div>

        {{-- Hidden items — populated on submit --}}
        <div id="hidden-items-container"></div>
        <input type="hidden" name="send_for_approval" :value="sendForApproval ? 1 : 0">

        {{-- Actions --}}
        <div class="flex justify-between items-center">
            <a href="{{ route('service-requests.show', $serviceRequest) }}"
               class="text-sm text-gray-500 hover:text-cyan-400 underline">&larr; Back to SR #{{ $serviceRequest->id }}</a>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 text-xs text-gray-400">
                    <input type="checkbox" x-model="sendForApproval" class="rounded border-white/20 text-cyan-500 focus:ring-cyan-500">
                    Send for approval after create
                </label>
                <button type="submit" x-bind:disabled="items.length === 0"
                        class="bg-blue-600 text-white font-medium px-6 py-2.5 rounded-md  shadow-xs transition disabled:opacity-50 disabled:cursor-not-allowed"
                        x-text="sendForApproval ? 'Create & Send Approval' : 'Create Estimate'">
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function estimateForm(initialItems) {
    return {
        stateCode: @json($stateCode ?? ''),
        taxRate: @json($taxRate ?? 0),
        taxRateFromDb: @json($taxRate !== null),
        items: Array.isArray(initialItems) ? initialItems : [],
        hasPrefill: Array.isArray(initialItems) && initialItems.length > 0,
        depositRequired: false,
        depositAmount: 0,
        sendForApproval: false,
        subtotal: 0,
        taxAmount: 0,
        total: 0,

        init() {
            this.recalculate();
        },

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

            if (!this.depositRequired) {
                this.depositAmount = 0;
            }
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
