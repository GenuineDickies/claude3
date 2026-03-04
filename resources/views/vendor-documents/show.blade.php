@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('vendor-documents.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        All Documents
    </a>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $doc->document_number }}</h1>
                @include('vendor-documents._status-badge', ['status' => $doc->status])
            </div>
            <p class="text-sm text-gray-500 mt-1">
                {{ ucfirst($doc->document_type) }} &middot;
                {{ $doc->document_date->format('M j, Y') }}
                @if ($doc->vendor_document_number)
                    &middot; Vendor # {{ $doc->vendor_document_number }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($doc->isDraft())
                <a href="{{ route('vendor-documents.edit', $doc) }}"
                   class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Edit
                </a>
                <form method="POST" action="{{ route('vendor-documents.post', $doc) }}" class="inline"
                      onsubmit="return confirm('Post this document? This will create journal entries and cannot be easily undone.')">
                    @csrf
                    <button class="px-3 py-1.5 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors">
                        Post to GL
                    </button>
                </form>
            @endif
            @if ($doc->isPosted())
                <form method="POST" action="{{ route('vendor-documents.void', $doc) }}" class="inline"
                      onsubmit="return confirm('Void this document? The journal entry will be reversed.')">
                    @csrf
                    <button class="px-3 py-1.5 text-sm font-semibold text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors">
                        Void
                    </button>
                </form>
                @if ($doc->isInvoice() && !$doc->is_paid)
                    <form method="POST" action="{{ route('vendor-documents.pay', $doc) }}" class="inline"
                          onsubmit="return confirm('Mark this invoice as paid? This will create the bill-payment journal entry.')">
                        @csrf
                        <div class="inline-flex items-center gap-2">
                            <select name="payment_method" class="rounded-md border-gray-300 text-xs">
                                @foreach (\App\Models\VendorDocument::PAYMENT_METHODS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                                Record Payment
                            </button>
                        </div>
                    </form>
                @endif
            @endif
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">{{ session('error') }}</div>
    @endif

    {{-- Vendor & payment info --}}
    <div class="bg-white rounded-lg shadow-sm p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Vendor</h3>
            <p class="font-medium text-gray-900">
                <a href="{{ route('vendors.show', $doc->vendor) }}" class="text-blue-600 hover:underline">{{ $doc->vendor->name }}</a>
            </p>
            @if ($doc->vendor->phone)
                <p class="text-sm text-gray-500">{{ $doc->vendor->phone }}</p>
            @endif
        </div>
        <div>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Payment</h3>
            @if ($doc->is_paid)
                <span class="inline-flex items-center gap-1 text-green-700 font-medium text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Paid
                    @if ($doc->payment_method)
                        ({{ \App\Models\VendorDocument::PAYMENT_METHODS[$doc->payment_method] ?? $doc->payment_method }})
                    @endif
                    @if ($doc->paid_date)
                        on {{ $doc->paid_date->format('M j, Y') }}
                    @endif
                </span>
            @else
                <span class="text-yellow-600 font-medium text-sm">Unpaid</span>
            @endif
        </div>
    </div>

    {{-- Line Items --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Line Items</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500 text-xs uppercase tracking-wider">
                        <th class="pb-2 pr-4">Type</th>
                        <th class="pb-2 pr-4">Description</th>
                        <th class="pb-2 pr-4 text-right">Qty</th>
                        <th class="pb-2 pr-4 text-right">Unit Cost</th>
                        <th class="pb-2 pr-4 text-right">Core $</th>
                        <th class="pb-2 pr-4">Expense Account</th>
                        <th class="pb-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($doc->lines as $line)
                        <tr class="border-b border-gray-50">
                            <td class="py-2 pr-4">
                                <span class="inline-block px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">{{ \App\Models\VendorDocumentLine::TYPES[$line->line_type] ?? $line->line_type }}</span>
                            </td>
                            <td class="py-2 pr-4 text-gray-900">{{ $line->description }}</td>
                            <td class="py-2 pr-4 text-right">{{ rtrim(rtrim(number_format($line->qty, 3), '0'), '.') }}</td>
                            <td class="py-2 pr-4 text-right">${{ number_format($line->unit_cost, 2) }}</td>
                            <td class="py-2 pr-4 text-right">
                                @if ($line->core_amount > 0)
                                    ${{ number_format($line->core_amount, 2) }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4 text-xs text-gray-500">
                                @if ($line->expenseAccount)
                                    {{ $line->expenseAccount->code }} {{ $line->expenseAccount->name }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-right font-medium">${{ number_format($line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 font-bold text-base">
                        <td colspan="6" class="py-3 text-right">Total</td>
                        <td class="py-3 text-right">${{ number_format($doc->total_amount, 2) }}</td>
                    </tr>
                    @if ($doc->totalCoreCharges() > 0)
                        <tr class="text-sm text-gray-500">
                            <td colspan="6" class="py-1 text-right">Core Charges Included</td>
                            <td class="py-1 text-right">${{ number_format($doc->totalCoreCharges(), 2) }}</td>
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Attachments --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Attachments</h2>
        @if ($doc->attachments->isEmpty())
            <p class="text-sm text-gray-400">No attachments.</p>
        @else
            <ul class="divide-y">
                @foreach ($doc->attachments as $att)
                    <li class="flex items-center justify-between py-2">
                        <a href="{{ route('vendor-documents.download-attachment', [$doc, $att]) }}"
                           class="text-sm text-blue-600 hover:underline truncate mr-4">
                            {{ $att->original_filename }}
                        </a>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-400">{{ number_format($att->file_size / 1024, 0) }} KB</span>
                            @if ($doc->isDraft())
                                <form method="POST" action="{{ route('vendor-documents.delete-attachment', [$doc, $att]) }}"
                                      onsubmit="return confirm('Delete this attachment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-400 hover:text-red-600 text-xs">Delete</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Accounting Links / Journal Entries --}}
    @if ($doc->accountingLinks->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Journal Entries</h2>
            <div class="space-y-3">
                @foreach ($doc->accountingLinks as $link)
                    @php $je = $link->journalEntry; @endphp
                    @if ($je)
                        <div class="border rounded-md p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-sm">{{ $je->entry_number }}</span>
                                <span class="text-xs px-2 py-0.5 rounded {{ $je->status === 'posted' ? 'bg-green-100 text-green-700' : ($je->status === 'void' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($je->status) }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mb-2">{{ $link->link_type }} &middot; {{ $je->entry_date->format('M j, Y') }}</p>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-gray-400 border-b">
                                        <th class="text-left pb-1">Account</th>
                                        <th class="text-right pb-1">Debit</th>
                                        <th class="text-right pb-1">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($je->lines as $jl)
                                        <tr class="border-b border-gray-50">
                                            <td class="py-1">{{ $jl->account->code ?? '' }} {{ $jl->account->name ?? '' }}</td>
                                            <td class="py-1 text-right">{{ $jl->debit > 0 ? '$'.number_format($jl->debit, 2) : '' }}</td>
                                            <td class="py-1 text-right">{{ $jl->credit > 0 ? '$'.number_format($jl->credit, 2) : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Notes --}}
    @if ($doc->notes)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Notes</h3>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $doc->notes }}</p>
        </div>
    @endif
</div>
@endsection
