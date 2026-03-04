@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('vendor-documents.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        All Vendor Documents
    </a>

    <h1 class="text-2xl font-bold text-gray-900">New Vendor Document</h1>

    <form method="POST" action="{{ route('vendor-documents.store') }}" class="space-y-6" x-data="vendorDocForm()">
        @csrf

        {{-- Document header --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Document Info</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="vendor_id" class="block text-sm font-medium text-gray-700 mb-1">Vendor <span class="text-red-500">*</span></label>
                    <select name="vendor_id" id="vendor_id"
                            class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select vendor…</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id', $preselectedVendor) == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                    @error('vendor_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                    <select name="document_type" id="document_type" x-model="docType"
                            class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                        @foreach (\App\Models\VendorDocument::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ old('document_type', 'receipt') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('document_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="document_date" class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="document_date" id="document_date" value="{{ old('document_date', now()->format('Y-m-d')) }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('document_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="vendor_document_number" class="block text-sm font-medium text-gray-700 mb-1">Vendor Doc #</label>
                    <input type="text" name="vendor_document_number" id="vendor_document_number" value="{{ old('vendor_document_number') }}"
                           placeholder="Vendor's receipt/invoice number"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- Payment info --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Payment</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" value="1" x-model="isPaid"
                               :checked="docType === 'receipt'"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Paid</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Receipts are typically paid at time of purchase</p>
                </div>
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select name="payment_method" id="payment_method"
                            class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">—</option>
                        @foreach (\App\Models\VendorDocument::PAYMENT_METHODS as $key => $label)
                            <option value="{{ $key }}" {{ old('payment_method') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Line Items</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-2 pr-2 w-28">Type</th>
                            <th class="pb-2 pr-2">Description</th>
                            <th class="pb-2 pr-2 w-20">Qty</th>
                            <th class="pb-2 pr-2 w-28">Unit Cost</th>
                            <th class="pb-2 pr-2 w-24">Core $</th>
                            <th class="pb-2 pr-2 w-40">Expense Acct</th>
                            <th class="pb-2 w-24 text-right">Amount</th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr class="border-b border-gray-100">
                                <td class="py-2 pr-2">
                                    <select :name="'lines[' + index + '][line_type]'" x-model="line.line_type"
                                            class="w-full rounded-md border-gray-300 shadow-xs text-xs focus:border-blue-500 focus:ring-blue-500" required>
                                        @foreach (\App\Models\VendorDocumentLine::TYPES as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="text" :name="'lines[' + index + '][description]'" x-model="line.description"
                                           placeholder="Description"
                                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <input type="hidden" :name="'lines[' + index + '][part_id]'" x-model="line.part_id">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="'lines[' + index + '][qty]'" x-model.number="line.qty"
                                           step="0.001" min="0.001" @input="recalculate()"
                                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                </td>
                                <td class="py-2 pr-2">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 text-xs">$</span>
                                        <input type="number" :name="'lines[' + index + '][unit_cost]'" x-model.number="line.unit_cost"
                                               step="0.01" min="0" @input="recalculate()"
                                               class="w-full pl-5 rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    </div>
                                </td>
                                <td class="py-2 pr-2">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400 text-xs">$</span>
                                        <input type="number" :name="'lines[' + index + '][core_amount]'" x-model.number="line.core_amount"
                                               step="0.01" min="0"
                                               class="w-full pl-5 rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </td>
                                <td class="py-2 pr-2">
                                    <select :name="'lines[' + index + '][expense_account_id]'" x-model="line.expense_account_id"
                                            class="w-full rounded-md border-gray-300 shadow-xs text-xs focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Default</option>
                                        @foreach ($expenseAccounts as $acct)
                                            <option value="{{ $acct->id }}">{{ $acct->code }} {{ $acct->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" :name="'lines[' + index + '][taxable]'" :value="line.taxable ? 1 : 0">
                                </td>
                                <td class="py-2 text-right font-medium text-sm" x-text="'$' + (line.qty * line.unit_cost).toFixed(2)"></td>
                                <td class="py-2 text-center">
                                    <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                            class="text-red-400 hover:text-red-600" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <button type="button" @click="addLine()"
                    class="mt-3 text-sm text-blue-600 hover:text-blue-800 underline">+ Add Line</button>

            {{-- Totals --}}
            <div class="mt-4 border-t pt-4 space-y-2 max-w-xs ml-auto text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium" x-text="'$' + subtotal.toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-base font-bold border-t pt-2">
                    <span>Total</span>
                    <span x-text="'$' + total.toFixed(2)"></span>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('vendor-documents.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                Save as Draft
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function vendorDocForm() {
    return {
        docType: '{{ old('document_type', 'receipt') }}',
        isPaid: {{ old('is_paid', 'true') }},
        lines: [{ line_type: 'part', description: '', part_id: '', qty: 1, unit_cost: 0, core_amount: 0, taxable: false, expense_account_id: '' }],
        subtotal: 0,
        total: 0,

        init() {
            this.$watch('docType', (val) => { if (val === 'receipt') this.isPaid = true; });
            this.recalculate();
        },

        recalculate() {
            this.subtotal = this.lines.reduce((sum, l) => sum + (l.qty || 0) * (l.unit_cost || 0), 0);
            this.total = this.subtotal;
        },

        addLine() {
            this.lines.push({ line_type: 'part', description: '', part_id: '', qty: 1, unit_cost: 0, core_amount: 0, taxable: false, expense_account_id: '' });
        },

        removeLine(index) {
            this.lines.splice(index, 1);
            this.recalculate();
        },
    };
}
</script>
@endpush
@endsection
