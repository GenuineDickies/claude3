{{-- Transaction Imports Show: preserves sub-nav with breadcrumb, bulk accept/reject forms, flash messages, stats+progress bar, collapsible accounting summary, desktop table + mobile cards, per-row accept/reject/edit forms, edit modal with category/account selects, AI summary --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Sub-navigation with breadcrumb --}}
    @include('documents._sub-nav', ['breadcrumb' => Str::limit($document->original_filename, 40)])

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Review Transactions</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $imports->count() }} row(s) parsed by AI
            </p>
        </div>
        @if ($stats['draft'] > 0)
            <div class="flex gap-2 shrink-0">
                <form method="POST" action="{{ route('transaction-imports.bulk-accept', $document) }}"
                      onsubmit="return confirm('Accept all {{ $stats['draft'] }} pending transactions and create expense records?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Accept All ({{ $stats['draft'] }})
                    </button>
                </form>
                <form method="POST" action="{{ route('transaction-imports.bulk-reject', $document) }}"
                      onsubmit="return confirm('Reject all {{ $stats['draft'] }} pending transactions?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 border border-red-500/30 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Reject All
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-500/30 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 px-4 py-3 rounded-lg text-sm">{{ session('info') }}</div>
    @endif

    {{-- Combined Stats + Progress --}}
    <div class="surface-1 p-4">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <div class="flex items-center gap-2">
                <span class="text-gray-500">Total:</span>
                <span class="font-bold text-white">{{ $stats['total'] }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="text-gray-500">Pending:</span>
                <span class="font-bold text-amber-600">{{ $stats['draft'] }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500/100"></span>
                <span class="text-gray-500">Accepted:</span>
                <span class="font-bold text-green-400">{{ $stats['accepted'] }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                <span class="text-gray-500">Rejected:</span>
                <span class="font-bold text-red-400">{{ $stats['rejected'] }}</span>
            </div>
            <div class="flex items-center gap-2 ml-auto">
                <span class="text-gray-500">Pending Total:</span>
                <span class="font-bold text-white">${{ number_format($stats['sum'], 2) }}</span>
            </div>
        </div>
        @php
            $reviewedCount = $stats['accepted'] + $stats['rejected'];
            $progressPct = $stats['total'] > 0 ? round($reviewedCount / $stats['total'] * 100) : 0;
            $acceptPct = $stats['total'] > 0 ? round($stats['accepted'] / $stats['total'] * 100) : 0;
            $rejectPct = $stats['total'] > 0 ? round($stats['rejected'] / $stats['total'] * 100) : 0;
        @endphp
        <div class="mt-3">
            <div class="w-full bg-white/5 rounded-full h-2 flex overflow-hidden">
                @if ($acceptPct > 0)
                    <div class="bg-green-500/100 h-full" style="width: {{ $acceptPct }}%"></div>
                @endif
                @if ($rejectPct > 0)
                    <div class="bg-red-400 h-full" style="width: {{ $rejectPct }}%"></div>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-1 text-right">{{ $progressPct }}% reviewed</p>
        </div>
    </div>

    {{-- Collapsible Accounting Summary --}}
    @if ($imports->isNotEmpty())
    @php
        $incomeTotal = $typeBreakdown['income']['total'] ?? 0;
        $expenseTotal = $typeBreakdown['expense']['total'] ?? 0;
        $transferTotal = $typeBreakdown['transfer']['total'] ?? 0;
        $net = $incomeTotal - $expenseTotal;
    @endphp
    <div x-data="{ summaryOpen: false }" class="surface-1">
        <button @click="summaryOpen = !summaryOpen" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-white/5 transition-colors">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-gray-300">Accounting Summary</h3>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    @if (isset($typeBreakdown['expense']))
                        <span class="text-red-400">${{ number_format($expenseTotal, 2) }} expense</span>
                    @endif
                    @if (isset($typeBreakdown['income']))
                        <span class="text-green-400">${{ number_format($incomeTotal, 2) }} income</span>
                    @endif
                    @if ($incomeTotal > 0 || $expenseTotal > 0)
                        <span class="font-medium {{ $net >= 0 ? 'text-green-700' : 'text-red-700' }}">Net: {{ $net >= 0 ? '+' : '-' }}${{ number_format(abs($net), 2) }}</span>
                    @endif
                </div>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="summaryOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>
        <div x-show="summaryOpen" x-collapse x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 px-4 pb-4">
                {{-- Income vs Expense breakdown --}}
                <div class="space-y-2">
                    @if (isset($typeBreakdown['income']))
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500/100"></span>
                                <span class="text-sm text-gray-300">Income ({{ $typeBreakdown['income']['count'] }})</span>
                            </div>
                            <span class="text-sm font-medium text-green-400">${{ number_format($incomeTotal, 2) }}</span>
                        </div>
                    @endif
                    @if (isset($typeBreakdown['expense']))
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                <span class="text-sm text-gray-300">Expenses ({{ $typeBreakdown['expense']['count'] }})</span>
                            </div>
                            <span class="text-sm font-medium text-red-400">${{ number_format($expenseTotal, 2) }}</span>
                        </div>
                    @endif
                    @if (isset($typeBreakdown['transfer']))
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-500/100"></span>
                                <span class="text-sm text-gray-300">Transfers ({{ $typeBreakdown['transfer']['count'] }})</span>
                            </div>
                            <span class="text-sm font-medium text-cyan-400">${{ number_format($transferTotal, 2) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Category breakdown --}}
                <div>
                    <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">By Category</h4>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        @foreach ($categoryBreakdown as $catKey => $cat)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-400">{{ $cat['label'] }} <span class="text-gray-400">({{ $cat['count'] }})</span></span>
                                <span class="font-medium text-white">${{ number_format($cat['total'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Transaction Table (desktop) / Cards (mobile) --}}
    <div class="surface-1 overflow-hidden">
        {{-- Desktop table --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="table-crystal min-w-full divide-y divide-white/5">
                <thead class="bg-white/5 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-transparent divide-y divide-white/5">
                    @forelse ($imports as $row)
                        <tr class="{{ $row->status === 'accepted' ? 'bg-green-500/10/50' : ($row->status === 'rejected' ? 'bg-red-50/50 opacity-60' : '') }}">
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                @if ($row->status === 'draft')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Draft</span>
                                @elseif ($row->status === 'accepted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Accepted</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-sm text-white">{{ $row->transaction_date?->format('m/d/Y') ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-sm text-white max-w-xs truncate" title="{{ $row->description }}">{{ Str::limit($row->description, 45) }}</td>
                            <td class="px-3 py-2.5 text-sm text-gray-300 whitespace-nowrap">{{ Str::limit($row->vendor ?? '—', 20) }}</td>
                            <td class="px-3 py-2.5 text-sm text-gray-300 whitespace-nowrap">
                                {{ $row->categoryLabel() }}
                                @if ($row->type === 'income')
                                    <span class="text-[10px] text-green-400 font-medium ml-1">IN</span>
                                @elseif ($row->type === 'transfer')
                                    <span class="text-[10px] text-cyan-400 font-medium ml-1">TFR</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-sm font-medium text-right {{ $row->type === 'expense' ? 'text-red-400' : 'text-green-400' }}">
                                ${{ number_format($row->amount, 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-sm text-gray-300 whitespace-nowrap">{{ $row->account_code ?? '—' }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-center">
                                @if ($row->status === 'draft')
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="openEditModal({{ $row->id }})"
                                                class="p-1.5 text-cyan-400 hover:bg-cyan-500/10 rounded" title="Edit & Accept">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </button>
                                        <form method="POST" action="{{ route('transaction-imports.accept', $row) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-green-400 hover:bg-green-500/10 rounded" title="Accept">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('transaction-imports.reject', $row) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-red-400 hover:bg-red-500/10 rounded" title="Reject">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($row->status === 'accepted')
                                    <a href="{{ route('expenses.show', $row->created_expense_id) }}" class="text-xs text-cyan-400 hover:underline">View Expense</a>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No transactions parsed for this document.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card layout --}}
        <div class="lg:hidden divide-y divide-gray-100">
            @forelse ($imports as $row)
                <div class="p-4 {{ $row->status === 'accepted' ? 'bg-green-500/10/50' : ($row->status === 'rejected' ? 'bg-red-50/30 opacity-70' : '') }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                @if ($row->status === 'draft')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Draft</span>
                                @elseif ($row->status === 'accepted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Accepted</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                @endif
                                <span class="text-xs text-gray-500">{{ $row->transaction_date?->format('m/d/Y') ?? '—' }}</span>
                            </div>
                            <p class="text-sm font-medium text-white mt-1.5">{{ Str::limit($row->description, 60) }}</p>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1 text-xs text-gray-500">
                                @if ($row->vendor)
                                    <span>{{ $row->vendor }}</span>
                                @endif
                                <span>{{ $row->categoryLabel() }}</span>
                                @if ($row->account_code)
                                    <span>Acct: {{ $row->account_code }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold {{ $row->type === 'expense' ? 'text-red-400' : 'text-green-400' }}">
                                ${{ number_format($row->amount, 2) }}
                            </p>
                            <p class="text-[10px] text-gray-400 uppercase">{{ $row->type }}</p>
                        </div>
                    </div>
                    @if ($row->status === 'draft')
                        <div class="flex items-center gap-2 mt-3 pt-2 border-t border-white/10">
                            <button onclick="openEditModal({{ $row->id }})"
                                    class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-medium text-cyan-400 bg-cyan-500/10 rounded-md hover:bg-blue-100 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                Edit
                            </button>
                            <form method="POST" action="{{ route('transaction-imports.accept', $row) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-medium text-green-700 bg-green-500/10 rounded-md hover:bg-green-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Accept
                                </button>
                            </form>
                            <form method="POST" action="{{ route('transaction-imports.reject', $row) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-md hover:bg-red-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    Reject
                                </button>
                            </form>
                        </div>
                    @elseif ($row->status === 'accepted')
                        <div class="mt-2">
                            <a href="{{ route('expenses.show', $row->created_expense_id) }}" class="text-xs text-cyan-400 hover:underline">View Expense &rarr;</a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">No transactions parsed for this document.</div>
            @endforelse
        </div>
    </div>

    {{-- AI Analysis Info --}}
    @if ($document->ai_summary)
        <div class="surface-1 p-4">
            <h3 class="text-sm font-semibold text-gray-300 mb-2">AI Document Summary</h3>
            <p class="text-sm text-gray-400">{{ $document->ai_summary }}</p>
        </div>
    @endif
</div>

{{-- Edit Modal --}}
<div id="editModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeEditModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="surface-2 rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto relative">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Edit Transaction Before Accepting</h3>
                <form id="editForm" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                            <input type="text" name="description" id="edit_description"
                                   class="w-full border-white/10 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Amount</label>
                                <input type="number" name="amount" id="edit_amount" step="0.01" min="0.01"
                                       class="w-full border-white/10 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Vendor</label>
                                <input type="text" name="vendor" id="edit_vendor"
                                       class="w-full border-white/10 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                                <select name="category" id="edit_category"
                                        class="w-full border-white/10 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-blue-500 text-sm">
                                    @foreach ($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Payment Method</label>
                                <select name="payment_method" id="edit_payment_method"
                                        class="w-full border-white/10 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-blue-500 text-sm">
                                    @foreach ($paymentMethods as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Account Code</label>
                            <select name="account_code" id="edit_account_code"
                                    class="w-full border-white/10 rounded-lg shadow-sm focus:ring-cyan-500 focus:border-blue-500 text-sm">
                                <option value="">— Auto —</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->code }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeEditModal()"
                                class="px-4 py-2 text-sm text-gray-300 bg-white/5 rounded-lg hover:bg-white/10">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700">
                            Save &amp; Accept
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@php
    $txDataJson = $imports->where('status', 'draft')->keyBy('id')->map(fn ($r) => [
        'description'    => $r->description,
        'amount'         => $r->amount,
        'vendor'         => $r->vendor,
        'category'       => $r->category,
        'payment_method' => $r->payment_method,
        'account_code'   => $r->account_code,
    ]);
@endphp
<script>
    // Transaction data keyed by import ID, used for the edit modal
    const txData = @json($txDataJson);

    function openEditModal(id) {
        const data = txData[id];
        if (!data) return;

        document.getElementById('editForm').action = '/transaction-imports/' + id + '/accept';
        document.getElementById('edit_description').value = data.description || '';
        document.getElementById('edit_amount').value = data.amount || '';
        document.getElementById('edit_vendor').value = data.vendor || '';
        document.getElementById('edit_category').value = data.category || 'other';
        document.getElementById('edit_payment_method').value = data.payment_method || 'card';
        document.getElementById('edit_account_code').value = data.account_code || '';
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Close modal on Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeEditModal();
    });
</script>
@endsection
