{{-- Transaction Imports Index: preserves documents sub-nav, stats bar, filter tabs, document list cards with progress bars and status badges, pagination --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Sub-navigation --}}
    @include('documents._sub-nav')

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-white">Transaction Imports</h1>
        <p class="text-sm text-gray-500 mt-1">
            AI-parsed transactions from uploaded spreadsheets. Review and accept to create expense &amp; journal records.
        </p>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="surface-1 p-4 text-center">
            <div class="text-2xl font-bold text-white">{{ $stats['total_docs'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Documents</div>
        </div>
        <div class="surface-1 p-4 text-center">
            <div class="text-2xl font-bold text-white">{{ $stats['total_rows'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Rows</div>
        </div>
        <div class="surface-1 p-4 text-center">
            <div class="text-2xl font-bold text-amber-600">{{ $stats['draft'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Pending Review</div>
        </div>
        <div class="surface-1 p-4 text-center">
            <div class="text-2xl font-bold text-green-400">{{ $stats['accepted'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Accepted</div>
        </div>
        <div class="surface-1 p-4 text-center">
            <div class="text-2xl font-bold text-red-400">{{ $stats['rejected'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Rejected</div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex gap-2 border-b">
        @foreach ([
            'all'      => 'All',
            'pending'  => 'Needs Review',
            'reviewed' => 'Reviewed',
        ] as $key => $label)
            <a href="{{ route('transaction-imports.index', ['filter' => $key]) }}"
               class="px-4 py-2 text-sm font-medium border-b-2 -mb-px
                      {{ $filter === $key ? 'border-blue-600 text-cyan-400' : 'border-transparent text-gray-500 hover:text-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Document List --}}
    @if ($documents->isEmpty())
        <div class="surface-1 p-8 text-center text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25" />
            </svg>
            <p class="text-lg font-medium">No spreadsheets with parsed transactions yet.</p>
            <p class="text-sm mt-2">Upload spreadsheets via the <a href="{{ route('inbox.index') }}" class="text-cyan-400 hover:underline font-medium">Document Inbox</a> to get started.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($documents as $doc)
                @php
                    $total = $doc->transaction_imports_count;
                    $reviewed = ($doc->accepted_count ?? 0) + ($doc->rejected_count ?? 0);
                    $pct = $total > 0 ? round($reviewed / $total * 100) : 0;
                    $allDone = $total > 0 && $doc->draft_count === 0;
                @endphp
                <a href="{{ route('transaction-imports.show', $doc) }}"
                   class="block surface-1 hover:shadow-md transition-shadow {{ $allDone ? 'border-green-500/30' : '' }}">
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                {{-- Spreadsheet icon --}}
                                <div class="w-10 h-10 rounded-lg {{ $allDone ? 'bg-green-500/10' : 'bg-amber-50' }} flex items-center justify-center shrink-0">
                                    @if ($allDone)
                                        <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    @else
                                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M10.875 12c-.621 0-1.125.504-1.125 1.125M12 12c.621 0 1.125.504 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 0v1.5c0 .621-.504 1.125-1.125 1.125M12 15.375c0-.621-.504-1.125-1.125-1.125" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-white truncate">{{ $doc->original_filename }}</div>
                                    <div class="text-xs text-gray-500">
                                        Uploaded {{ $doc->created_at->diffForHumans() }}
                                        &middot; {{ $total }} transaction(s)
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <div class="hidden sm:flex items-center gap-2 text-xs">
                                    @if ($doc->draft_count > 0)
                                        <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-800 font-medium">{{ $doc->draft_count }} pending</span>
                                    @endif
                                    @if ($doc->accepted_count > 0)
                                        <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 font-medium">{{ $doc->accepted_count }} accepted</span>
                                    @endif
                                    @if ($doc->rejected_count > 0)
                                        <span class="px-2 py-1 rounded-full bg-red-100 text-red-800 font-medium">{{ $doc->rejected_count }} rejected</span>
                                    @endif
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        @if ($total > 0)
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>{{ $reviewed }} of {{ $total }} reviewed</span>
                                    <span>{{ $pct }}%</span>
                                </div>
                                <div class="w-full bg-white/5 rounded-full h-1.5">
                                    @if ($total > 0)
                                        @php
                                            $acceptPct = round(($doc->accepted_count ?? 0) / $total * 100);
                                            $rejectPct = round(($doc->rejected_count ?? 0) / $total * 100);
                                        @endphp
                                        <div class="h-1.5 rounded-full flex overflow-hidden">
                                            @if ($acceptPct > 0)
                                                <div class="bg-green-500/100 h-full" style="width: {{ $acceptPct }}%"></div>
                                            @endif
                                            @if ($rejectPct > 0)
                                                <div class="bg-red-400 h-full" style="width: {{ $rejectPct }}%"></div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $documents->links() }}
        </div>
    @endif
</div>
@endsection
