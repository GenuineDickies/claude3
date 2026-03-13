@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('invoices.show', [$serviceRequest, $invoice]) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Invoice {{ $invoice->displayNumber() }}
    </a>

    <h1 class="text-2xl font-bold text-white">Edit Invoice {{ $invoice->displayNumber() }}</h1>

    <form method="POST" action="{{ route('invoices.update', [$serviceRequest, $invoice]) }}" class="space-y-6" x-data="invoiceEditForm()">
        @csrf
        @method('PUT')

        {{-- Customer info --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Customer</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" id="customer_name"
                           value="{{ old('customer_name', $invoice->customer_name) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    @error('customer_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="customer_phone" class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                    <input type="text" name="customer_phone" id="customer_phone"
                           value="{{ old('customer_phone', $invoice->customer_phone) }}"
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
                           value="{{ old('vehicle_description', $invoice->vehicle_description) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="service_description" class="block text-sm font-medium text-gray-300 mb-1">Service Type</label>
                    <input type="text" name="service_description" id="service_description"
                           value="{{ old('service_description', $invoice->service_description) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div class="sm:col-span-2">
                    <label for="service_location" class="block text-sm font-medium text-gray-300 mb-1">Location</label>
                    <input type="text" name="service_location" id="service_location"
                           value="{{ old('service_location', $invoice->service_location) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
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
                           value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="payment_terms" class="block text-sm font-medium text-gray-300 mb-1">Payment Terms</label>
                    <input type="text" name="payment_terms" id="payment_terms"
                           value="{{ old('payment_terms', $invoice->payment_terms) }}"
                           placeholder="e.g. Net 30, Due on Receipt"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
            </div>
            <div class="mt-4">
                <label for="notes" class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                <textarea name="notes" id="notes" rows="2"
                          class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal resize-none">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="inline-flex items-center px-6 py-2 btn-crystal text-sm font-semibold rounded-md  transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Save Invoice
            </button>
            <a href="{{ route('invoices.show', [$serviceRequest, $invoice]) }}"
               class="px-4 py-2 border border-white/10 text-gray-300 text-sm font-medium rounded-md hover:bg-white/5 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function invoiceEditForm() {
    const existingItems = @json($invoice->line_items ?? []);

    return {
        items: existingItems.length ? existingItems.map(i => ({
            name: i.name || '',
            description: i.description || '',
            quantity: parseFloat(i.quantity) || 1,
            unit: i.unit || 'ea',
            unit_price: parseFloat(i.unit_price) || 0,
        })) : [{ name: '', description: '', quantity: 1, unit: 'ea', unit_price: 0 }],
        taxRate: {{ $invoice->tax_rate ?? 0 }},
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
