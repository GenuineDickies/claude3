{{--
  Create Invoice — invoices.create
  Preserved features: CSRF, Alpine invoiceForm, breadcrumb, customer info
  (prefilled from service request customer), and downstream invoice form
  sections (line items, totals, notes, actions) — all unchanged.
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Breadcrumb --}}
    <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Service Request #{{ $serviceRequest->id }}
    </a>

    <h1 class="text-2xl font-bold text-white">Create Invoice</h1>

    <form method="POST" action="{{ route('invoices.store', [$serviceRequest, $workOrder]) }}" class="space-y-6" x-data="invoiceForm()">
        @csrf

        {{-- Customer info --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Customer</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" id="customer_name"
                           value="{{ old('customer_name', ($serviceRequest->customer?->first_name ?? '') . ' ' . ($serviceRequest->customer?->last_name ?? '')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    @error('customer_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="customer_phone" class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                    <input type="text" name="customer_phone" id="customer_phone"
                           value="{{ old('customer_phone', $serviceRequest->customer?->phone) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
            </div>
        </div>

        {{-- Vehicle & Service --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Service Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="vehicle_description" class="block text-sm font-medium text-gray-300 mb-1">Vehicle</label>
                    <input type="text" name="vehicle_description" id="vehicle_description"
                           value="{{ old('vehicle_description', trim(implode(' ', array_filter([$serviceRequest->vehicle_color, $serviceRequest->vehicle_year, $serviceRequest->vehicle_make, $serviceRequest->vehicle_model])))) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="service_description" class="block text-sm font-medium text-gray-300 mb-1">Service Type</label>
                    <input type="text" name="service_description" id="service_description"
                           value="{{ old('service_description', $serviceRequest->catalogItem?->name) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div class="sm:col-span-2">
                    <label for="service_location" class="block text-sm font-medium text-gray-300 mb-1">Location</label>
                    <input type="text" name="service_location" id="service_location"
                           value="{{ old('service_location', $serviceRequest->location) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
            </div>
        </div>

        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Persistent Vehicle Record</h2>
            <p class="mb-4 text-sm text-gray-400">A persistent vehicle record is attached at invoice stage. License plate or VIN is required here unless a vehicle record is already attached to this service request.</p>

            @if ($serviceRequest->vehicle)
                <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    Existing vehicle record attached: {{ $serviceRequest->vehicle->displayName() }}
                    @if ($serviceRequest->vehicle->license_plate)
                        | Plate {{ $serviceRequest->vehicle->license_plate }}
                    @endif
                    @if ($serviceRequest->vehicle->vin)
                        | VIN {{ $serviceRequest->vehicle->vin }}
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="vehicle_year" class="block text-sm font-medium text-gray-300 mb-1">Vehicle Year</label>
                    <input type="text" name="vehicle_year" id="vehicle_year" value="{{ old('vehicle_year', $serviceRequest->vehicle?->year ?? $serviceRequest->vehicle_year) }}" class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('vehicle_year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="vehicle_color" class="block text-sm font-medium text-gray-300 mb-1">Vehicle Color</label>
                    <input type="text" name="vehicle_color" id="vehicle_color" value="{{ old('vehicle_color', $serviceRequest->vehicle?->color ?? $serviceRequest->vehicle_color) }}" class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="vehicle_make" class="block text-sm font-medium text-gray-300 mb-1">Vehicle Make</label>
                    <input type="text" name="vehicle_make" id="vehicle_make" value="{{ old('vehicle_make', $serviceRequest->vehicle?->make ?? $serviceRequest->vehicle_make) }}" class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="vehicle_model" class="block text-sm font-medium text-gray-300 mb-1">Vehicle Model</label>
                    <input type="text" name="vehicle_model" id="vehicle_model" value="{{ old('vehicle_model', $serviceRequest->vehicle?->model ?? $serviceRequest->vehicle_model) }}" class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="vehicle_license_plate" class="block text-sm font-medium text-gray-300 mb-1">License Plate</label>
                    <input type="text" name="vehicle_license_plate" id="vehicle_license_plate" value="{{ old('vehicle_license_plate', $serviceRequest->vehicle?->license_plate) }}" class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('vehicle_license_plate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="vehicle_vin" class="block text-sm font-medium text-gray-300 mb-1">VIN</label>
                    <input type="text" name="vehicle_vin" id="vehicle_vin" value="{{ old('vehicle_vin', $serviceRequest->vehicle?->vin) }}" class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('vehicle_vin') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Line Items</h2>

            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-2 pr-2">Item</th>
                            <th class="pb-2 pr-2">Description</th>
                            <th class="pb-2 pr-2 w-20">Qty</th>
                            <th class="pb-2 pr-2 w-20">Unit</th>
                            <th class="pb-2 pr-2 w-28">Unit Price</th>
                            <th class="pb-2 w-24 text-right">Amount</th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-white/10">
                                <td class="py-2 pr-2">
                                    <input type="text" :name="'line_items[' + index + '][name]'" x-model="item.name"
                                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="text" :name="'line_items[' + index + '][description]'" x-model="item.description"
                                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="'line_items[' + index + '][quantity]'" x-model.number="item.quantity"
                                           step="0.01" min="0.01" @input="recalculate()"
                                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="text" :name="'line_items[' + index + '][unit]'" x-model="item.unit"
                                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="'line_items[' + index + '][unit_price]'" x-model.number="item.unit_price"
                                           step="0.01" min="0" @input="recalculate()"
                                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                                </td>
                                <td class="py-2 text-right font-medium" x-text="'$' + (item.quantity * item.unit_price).toFixed(2)"></td>
                                <td class="py-2 text-center">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                            class="text-red-400 hover:text-red-400" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <button type="button" @click="addItem()"
                    class="mt-3 text-sm text-cyan-400 hover:text-cyan-300 underline">+ Add Item</button>

            {{-- Totals --}}
            <div class="mt-4 border-t pt-4 space-y-2 max-w-xs ml-auto text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Subtotal</span>
                    <span class="font-medium" x-text="'$' + subtotal.toFixed(2)"></span>
                    <input type="hidden" name="subtotal" :value="subtotal.toFixed(2)">
                </div>
                <div class="flex justify-between items-center gap-2">
                    <span class="text-gray-400">Tax Rate (%)</span>
                    <input type="number" name="tax_rate" x-model.number="taxRate" @input="recalculate()"
                           step="0.01" min="0" max="100"
                           class="w-20 rounded-md border-white/10 shadow-xs text-sm text-right input-crystal">
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Tax</span>
                    <span class="font-medium" x-text="'$' + taxAmount.toFixed(2)"></span>
                    <input type="hidden" name="tax_amount" :value="taxAmount.toFixed(2)">
                </div>
                <div class="flex justify-between text-base font-bold border-t pt-2">
                    <span>Total</span>
                    <span x-text="'$' + total.toFixed(2)"></span>
                    <input type="hidden" name="total" :value="total.toFixed(2)">
                </div>
            </div>
        </div>

        {{-- Terms & Notes --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Terms & Notes</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-300 mb-1">Due Date</label>
                    <input type="date" name="due_date" id="due_date"
                           value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="payment_terms" class="block text-sm font-medium text-gray-300 mb-1">Payment Terms</label>
                    <input type="text" name="payment_terms" id="payment_terms"
                           value="{{ old('payment_terms', 'Net 30') }}"
                           placeholder="e.g. Net 30, Due on Receipt"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
            </div>
            <div class="mt-4">
                <label for="notes" class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                <textarea name="notes" id="notes" rows="2"
                          class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal resize-none">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="inline-flex items-center px-6 py-2 btn-crystal text-sm font-semibold rounded-md  transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Create Invoice
            </button>
            <a href="{{ route('service-requests.show', $serviceRequest) }}"
               class="px-4 py-2 border border-white/10 text-gray-300 text-sm font-medium rounded-md hover:bg-white/5 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function invoiceForm() {
    const workOrderItems = @json($workOrderItems);

    return {
        items: workOrderItems.length ? workOrderItems : [{ name: '', description: '', quantity: 1, unit: 'ea', unit_price: 0 }],
        taxRate: 0,
        subtotal: 0,
        taxAmount: 0,
        total: 0,

        init() {
            this.recalculate();
        },

        recalculate() {
            this.subtotal = this.items.reduce((sum, i) => sum + (i.quantity || 0) * (i.unit_price || 0), 0);
            this.taxAmount = Math.round(this.subtotal * (this.taxRate / 100) * 100) / 100;
            this.total = this.subtotal + this.taxAmount;
        },

        addItem() {
            this.items.push({ name: '', description: '', quantity: 1, unit: 'ea', unit_price: 0 });
        },

        removeItem(index) {
            this.items.splice(index, 1);
            this.recalculate();
        },
    };
}
</script>
@endpush
@endsection
