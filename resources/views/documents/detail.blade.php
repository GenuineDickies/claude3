@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Back link --}}
    <a href="{{ url()->previous() }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back
    </a>

    {{-- Header --}}
    <div class="surface-1 p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white break-all">{{ $document->original_filename }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ \App\Models\Document::CATEGORY_LABELS[$document->category] ?? $document->category }}
                    &middot; {{ $document->humanFileSize() }}
                    &middot; {{ $document->mime_type }}
                    @if ($document->uploader) &middot; Uploaded by {{ $document->uploader->name }} @endif
                    &middot; {{ $document->created_at->format('M j, Y g:ia') }}
                </p>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('documents.show', $document) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-white/5 border border-white/10 text-gray-300 text-sm font-medium rounded-md hover:bg-white/5 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Download
                </a>
                <form method="POST" action="{{ route('documents.destroy', $document) }}"
                      onsubmit="return confirm('Delete this document?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-transparent border border-red-500/30 text-red-400 text-sm font-medium rounded-md hover:bg-red-500/10 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- AI Analysis Status --}}
    <div class="surface-1 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-300">AI Analysis</h2>
            <div class="flex items-center gap-3">
                @php
                    $statusColors = match($document->ai_status) {
                        'pending'    => 'bg-white/5 text-gray-300',
                        'processing' => 'bg-blue-100 text-cyan-400',
                        'completed'  => 'bg-green-100 text-green-700',
                        'failed'     => 'bg-red-100 text-red-700',
                        default      => 'bg-white/5 text-gray-300',
                    };
                @endphp
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColors }}">
                    {{ ucfirst($document->ai_status) }}
                </span>

                @if ($document->ai_confidence !== null)
                    <span class="text-xs text-gray-500">
                        {{ number_format($document->ai_confidence * 100, 0) }}% confidence
                    </span>
                @endif
            </div>
        </div>

        @if ($document->isAiFailed())
            <div class="rounded-md bg-red-50 p-4 mb-4">
                <p class="text-sm text-red-700">{{ $document->ai_error ?? 'An unknown error occurred.' }}</p>
            </div>
            <form method="POST" action="{{ route('documents.reanalyze', $document) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md  transition-colors">
                    Retry Analysis
                </button>
            </form>
        @elseif ($document->isAiPending())
            <p class="text-sm text-gray-500">Analysis is pending or in progress. This page will show results once processing completes.</p>
        @elseif ($document->isAiCompleted())

            {{-- Category comparison --}}
            @if ($document->ai_suggested_category && $document->ai_suggested_category !== $document->category)
                <div class="rounded-md bg-amber-50 border border-amber-500/30 p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-amber-800">
                            <span class="font-medium">AI suggests:</span>
                            {{ \App\Models\Document::CATEGORY_LABELS[$document->ai_suggested_category] ?? $document->ai_suggested_category }}
                            <span class="text-amber-600">(currently: {{ \App\Models\Document::CATEGORY_LABELS[$document->category] ?? $document->category }})</span>
                        </p>
                        <form method="POST" action="{{ route('documents.accept-category', $document) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1 text-xs font-medium text-amber-800 bg-amber-200 rounded-md hover:bg-amber-300 transition-colors">
                                Accept
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Summary --}}
            @if ($document->ai_summary)
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Summary</h3>
                    <p class="text-sm text-gray-300 leading-relaxed">{{ $document->ai_summary }}</p>
                </div>
            @endif

            {{-- Tags --}}
            @if (! empty($document->ai_tags))
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Tags</h3>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($document->ai_tags as $tag)
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-500/10 text-cyan-400">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Extracted Data --}}
            @if (! empty($document->ai_extracted_data))
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Extracted Data</h3>
                    <div class="overflow-hidden rounded-lg border border-white/10">
                        <table class="table-crystal min-w-full divide-y divide-white/5 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($document->ai_extracted_data as $key => $value)
                                    <tr>
                                        <td class="px-4 py-2.5 font-medium text-gray-400 bg-white/5 whitespace-nowrap w-1/3">
                                            {{ str_replace('_', ' ', ucfirst($key)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-white">
                                            @if (is_array($value))
                                                <div class="space-y-1">
                                                    @foreach ($value as $item)
                                                        @if (is_array($item))
                                                            <div class="text-xs bg-white/5 rounded p-2 font-mono">
                                                                @foreach ($item as $k => $v)
                                                                    <span class="text-gray-500">{{ $k }}:</span> {{ $v ?? 'N/A' }}@if (! $loop->last), @endif
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span>{{ $item }}</span>@if (! $loop->last), @endif
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                {{ $value ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Re-analyze button --}}
            <div class="pt-4 border-t border-white/10">
                <form method="POST" action="{{ route('documents.reanalyze', $document) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-300 bg-white/5 border border-white/10 rounded-md hover:bg-white/5 transition-colors">
                        Re-analyze
                    </button>
                </form>
            </div>

            @if ($document->ai_processed_at)
                <p class="text-xs text-gray-400 mt-3">Analyzed {{ $document->ai_processed_at->diffForHumans() }}</p>
            @endif
        @endif
    </div>

    {{-- Line Items — per-item categorization for receipts/invoices --}}
    @if (isset($lineItems) && $lineItems->count() > 0)
    <div class="surface-1 p-6" x-data="lineItemManager()">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-300">Line Items</h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $lineItems->count() }} item(s) &middot;
                    <span class="text-green-400">{{ $lineItems->where('status', 'accepted')->count() }} accepted</span> &middot;
                    <span class="text-yellow-600">{{ $lineItems->where('status', 'draft')->count() }} pending</span> &middot;
                    <span class="text-red-400">{{ $lineItems->where('status', 'rejected')->count() }} rejected</span>
                </p>
            </div>
            @if ($lineItems->where('status', 'draft')->count() > 0)
                <div class="flex gap-2">
                    <button type="button" @click="submitBulkAccept()"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Accept All
                    </button>
                    <form method="POST" action="{{ route('line-items.bulk-reject', $document) }}"
                          onsubmit="return confirm('Reject all pending line items?')">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-400 bg-white/5 border border-red-500/30 rounded-md hover:bg-red-500/10 transition">
                            Reject All
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Draft line items — editable --}}
        @php $draftItems = $lineItems->where('status', 'draft'); @endphp
        @if ($draftItems->count() > 0)
        <form id="bulk-accept-form" method="POST" action="{{ route('line-items.bulk-accept', $document) }}">
            @csrf
            <div class="overflow-x-auto rounded-lg border border-white/10">
                <table class="table-crystal min-w-full divide-y divide-white/5 text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase w-20">Qty</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase w-24">Unit Price</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase w-24">Amount</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-40">Category</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-48">Account</th>
                            <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($draftItems as $index => $item)
                        <tr class="hover:bg-white/5" x-data="{ category: '{{ $item->category ?? '' }}' }">
                            <td class="px-4 py-2.5 text-white">
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                {{ $item->description }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-400">{{ $item->quantity ? number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 2) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-400">{{ $item->unit_price ? '$' . number_format($item->unit_price, 2) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-white">${{ number_format($item->amount, 2) }}</td>
                            <td class="px-4 py-2.5">
                                <select name="items[{{ $index }}][category]"
                                        x-model="category"
                                        @change="$dispatch('category-changed', { index: {{ $index }}, category: category })"
                                        class="w-full rounded-md border-white/10 text-xs py-1.5 input-crystal
                                        {{ $item->category ? 'text-white' : 'text-amber-600 border-amber-300' }}">
                                    <option value="">Select…</option>
                                    @foreach ($categories as $catKey => $catLabel)
                                        <option value="{{ $catKey }}" {{ $item->category === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2.5">
                                <select name="items[{{ $index }}][account_id]"
                                        class="w-full rounded-md border-white/10 text-xs py-1.5 input-crystal">
                                    <option value="">Select…</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}"
                                            {{ ($item->account_id == $account->id) ? 'selected' : '' }}>
                                            {{ $account->code }} — {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <form method="POST" action="{{ route('line-items.reject', $item) }}" class="inline">
                                        @csrf
                                        <button type="submit" title="Reject"
                                                class="p-1 text-red-400 hover:text-red-400 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-white/5">
                        <tr>
                            <td colspan="3" class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Total Pending</td>
                            <td class="px-4 py-2.5 text-right font-bold text-white">${{ number_format($draftItems->sum('amount'), 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>
        @endif

        {{-- Reviewed line items (accepted/rejected) --}}
        @php $reviewedItems = $lineItems->whereIn('status', ['accepted', 'rejected']); @endphp
        @if ($reviewedItems->count() > 0)
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Reviewed Items</h3>
            <div class="overflow-x-auto rounded-lg border border-white/10">
                <table class="table-crystal min-w-full divide-y divide-white/5 text-sm">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase w-24">Amount</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-32">Category</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-40">Account</th>
                            <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase w-24">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($reviewedItems as $item)
                        <tr class="{{ $item->status === 'rejected' ? 'bg-red-50/40' : 'bg-green-500/10/40' }}">
                            <td class="px-4 py-2.5 text-gray-300">{{ $item->description }}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-white">${{ number_format($item->amount, 2) }}</td>
                            <td class="px-4 py-2.5 text-gray-400">{{ $item->categoryLabel() }}</td>
                            <td class="px-4 py-2.5 text-gray-400 text-xs">
                                @if ($item->account)
                                    {{ $item->account->code }} — {{ $item->account->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @if ($item->status === 'accepted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        Accepted
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Rejected
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <script>
    function lineItemManager() {
        return {
            submitBulkAccept() {
                const form = document.getElementById('bulk-accept-form');
                if (!form) return;

                // Validate that all rows have category + account selected
                const selects = form.querySelectorAll('select');
                let valid = true;
                selects.forEach(sel => {
                    if (!sel.value) {
                        sel.classList.add('border-red-500', 'ring-red-500');
                        valid = false;
                    } else {
                        sel.classList.remove('border-red-500', 'ring-red-500');
                    }
                });

                if (!valid) {
                    alert('Please select a category and account for each line item before accepting all.');
                    return;
                }

                form.submit();
            }
        };
    }
    </script>
    @endif

</div>
@endsection
