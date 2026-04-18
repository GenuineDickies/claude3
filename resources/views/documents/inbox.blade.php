{{--
  Document Inbox — inbox.index
  Controller vars: $documents (paginator), $stats, $isProcessing, $categories, $autoAcceptable, $filter
  Features preserved:
    - documents._sub-nav include
    - Success/error flash messages
    - Inbox Summary stats (Total, Processing, Analyzed, Unmatched, Matched, Skipped, Failed)
    - Processing live indicator + auto-refresh script (when $isProcessing)
    - Category breakdown chips
    - Accept All bulk-accept form (when $autoAcceptable > 0)
    - Collapsible upload card (Alpine) with drag/drop, file input, mobile camera shortcut
    - Filter tabs (all/unmatched/matched/processing/failed/skipped)
    - Per-document card: AI status + match status badges, AI summary + extracted fields,
      Top match + Accept/Skip/Manual link buttons, Re-match form, View details/Download links
    - Manual Link Modal with type select + search w/ AJAX results + submit
    - All inline JS for drag/drop, file list, manual link search/submit
    - Pagination
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Sub-navigation --}}
    @include('documents._sub-nav')

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-white">Document Inbox</h1>
        <p class="text-sm text-gray-500 mt-1">Upload documents for AI-powered identification, data extraction, and auto-matching.</p>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-800 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-500/30 text-red-800 rounded-lg px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Dashboard --}}
    @if ($stats['total'] > 0)
    <div class="surface-1 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Inbox Summary</h2>
            @if ($isProcessing)
                <span class="inline-flex items-center gap-1.5 text-xs text-yellow-700 bg-yellow-50 px-2.5 py-1 rounded-full">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-500"></span>
                    </span>
                    Processing&hellip;
                </span>
            @endif
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
            <div class="bg-white/5 rounded-lg p-3">
                <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-500">Total</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3">
                <p class="text-2xl font-bold text-yellow-700">{{ $stats['processing'] }}</p>
                <p class="text-xs text-yellow-600">Processing</p>
            </div>
            <div class="bg-green-500/10 rounded-lg p-3">
                <p class="text-2xl font-bold text-green-700">{{ $stats['completed'] }}</p>
                <p class="text-xs text-green-400">Analyzed</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-3">
                <p class="text-2xl font-bold text-orange-700">{{ $stats['unmatched'] }}</p>
                <p class="text-xs text-orange-600">Unmatched</p>
            </div>
            <div class="bg-cyan-500/10 rounded-lg p-3">
                <p class="text-2xl font-bold text-cyan-400">{{ $stats['matched'] }}</p>
                <p class="text-xs text-cyan-400">Matched</p>
            </div>
            <div class="bg-white/5 rounded-lg p-3">
                <p class="text-2xl font-bold text-gray-500">{{ $stats['skipped'] }}</p>
                <p class="text-xs text-gray-500">Skipped</p>
            </div>
            <div class="bg-red-50 rounded-lg p-3">
                <p class="text-2xl font-bold text-red-700">{{ $stats['failed'] }}</p>
                <p class="text-xs text-red-400">Failed</p>
            </div>
        </div>

        {{-- Category breakdown --}}
        @if (!empty($categories))
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($categories as $cat => $count)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-cyan-300">
                    {{ \App\Models\Document::CATEGORY_LABELS[$cat] ?? ucfirst($cat) }}: {{ $count }}
                </span>
            @endforeach
        </div>
        @endif

        {{-- Accept All button --}}
        @if ($autoAcceptable > 0)
        <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-between">
            <p class="text-sm text-gray-400">
                <span class="font-semibold text-green-700">{{ $autoAcceptable }}</span> document(s) have high-confidence matches ready to accept.
            </p>
            <form method="POST" action="{{ route('inbox.bulk-accept') }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Accept All ({{ $autoAcceptable }})
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif

    {{-- Upload Card (collapsible) --}}
    <div x-data="{ uploadOpen: {{ $stats['total'] === 0 ? 'true' : 'false' }} }" class="surface-1">
        <button @click="uploadOpen = !uploadOpen" type="button"
                class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-white/5 rounded-lg transition-colors">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                </svg>
                <span class="text-lg font-semibold text-gray-300">Upload Documents</span>
            </div>
            <svg :class="uploadOpen && 'rotate-180'" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>
        <div x-show="uploadOpen" x-collapse class="px-6 pb-6">
        <form method="POST" action="{{ route('inbox.upload') }}" enctype="multipart/form-data"
              id="inbox-upload-form">
            @csrf
            <div class="border-2 border-dashed border-white/10 rounded-lg p-8 text-center hover:border-blue-400 transition cursor-pointer"
                 id="drop-zone"
                 onclick="document.getElementById('inbox-files').click()">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                </svg>
                <p class="mt-2 text-sm text-gray-400">
                    <span class="font-semibold text-cyan-400">Click to select files</span> or drag and drop
                </p>
                <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG, WEBP, HEIC, DOC, DOCX, XLS, XLSX &mdash; up to 1 GB each, max 100 files per batch</p>
                <input type="file" name="files[]" id="inbox-files" multiple accept=".jpg,.jpeg,.png,.webp,.heic,.heif,.pdf,.doc,.docx,.xls,.xlsx" class="hidden">

                {{-- Mobile camera shortcut --}}
                <div class="mt-3 sm:hidden">
                    <button type="button" onclick="document.getElementById('inbox-camera').click()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-cyan-500/10 text-cyan-400 text-sm font-medium rounded-lg hover:bg-blue-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"/></svg>
                        Take Photo
                    </button>
                    <input type="file" name="files[]" id="inbox-camera" accept="image/*" capture="environment" class="hidden"
                           onchange="if(this.files.length){document.getElementById('inbox-files').files=this.files; document.getElementById('inbox-files').dispatchEvent(new Event('change'))}">
                </div>
            </div>

            {{-- Selected file list --}}
            <div id="selected-files" class="hidden mt-3 space-y-1"></div>

            @error('files')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror
            @error('files.*')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex justify-end">
                <button type="submit" id="upload-btn" disabled
                        class="inline-flex items-center px-5 py-2.5 btn-crystal text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                    Upload &amp; Analyze
                </button>
            </div>
        </form>
        </div>
    </div>

    {{-- Filter tabs --}}
    <div class="flex gap-2 flex-wrap">
        @foreach (['all' => 'All', 'unmatched' => 'Unmatched', 'matched' => 'Matched', 'processing' => 'Processing', 'failed' => 'Failed', 'skipped' => 'Skipped'] as $key => $label)
            <a href="{{ route('inbox.index', ['filter' => $key]) }}"
               class="px-3 py-1.5 text-sm rounded-full transition {{ $filter === $key ? 'bg-blue-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Document list --}}
    @if ($documents->isEmpty())
        <div class="surface-1 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />
            </svg>
            <p class="mt-4 text-gray-500">No documents in the inbox{{ $filter !== 'all' ? ' matching this filter' : '' }}.</p>
            <p class="text-sm text-gray-400 mt-1">Upload documents above to get started.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($documents as $doc)
                <div class="surface-1 p-5 hover:shadow-md transition">
                    <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                        {{-- File info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('documents.detail', $doc) }}" class="font-semibold text-white hover:text-cyan-400 truncate">
                                    {{ $doc->original_filename }}
                                </a>

                                {{-- AI status badge --}}
                                @php
                                    $aiColors = match($doc->ai_status) {
                                        'completed'  => 'bg-green-100 text-green-800',
                                        'processing' => 'bg-yellow-100 text-yellow-800',
                                        'failed'     => 'bg-red-100 text-red-800',
                                        default      => 'bg-white/5 text-gray-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $aiColors }}">
                                    {{ ucfirst($doc->ai_status) }}
                                </span>

                                {{-- Match status badge --}}
                                @php
                                    $matchColors = match($doc->match_status) {
                                        'matched', 'manual' => 'bg-blue-100 text-blue-800',
                                        'skipped'           => 'bg-white/5 text-gray-500',
                                        default             => 'bg-orange-100 text-orange-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $matchColors }}">
                                    {{ ucfirst($doc->match_status ?? 'unmatched') }}
                                </span>
                            </div>

                            <p class="text-sm text-gray-500 mt-1">
                                {{ $doc->humanFileSize() }} &middot; {{ $doc->mime_type }}
                                @if ($doc->uploader) &middot; {{ $doc->uploader->name }} @endif
                                &middot; {{ $doc->created_at->diffForHumans() }}
                            </p>

                            {{-- AI extracted summary --}}
                            @if ($doc->ai_status === 'completed')
                                <div class="mt-2 text-sm text-gray-400">
                                    @if ($doc->ai_suggested_category)
                                        <span class="font-medium">Type:</span>
                                        {{ \App\Models\Document::CATEGORY_LABELS[$doc->ai_suggested_category] ?? $doc->ai_suggested_category }}
                                        @if ($doc->ai_confidence)
                                            <span class="text-gray-400">({{ number_format($doc->ai_confidence * 100) }}%)</span>
                                        @endif
                                        &middot;
                                    @endif
                                    @if ($doc->ai_summary)
                                        {{ \Illuminate\Support\Str::limit($doc->ai_summary, 120) }}
                                    @endif
                                </div>

                                {{-- Key extracted fields --}}
                                @php $ed = $doc->ai_extracted_data ?? []; @endphp
                                @if (!empty($ed))
                                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
                                        @foreach (['invoice_number', 'receipt_number', 'vendor_name', 'total_amount', 'amount', 'date', 'customer_name'] as $field)
                                            @if (!empty($ed[$field]))
                                                <span><span class="font-medium text-gray-400">{{ str_replace('_', ' ', ucfirst($field)) }}:</span> {{ $ed[$field] }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Match candidates & actions --}}
                        <div class="flex flex-col gap-2 lg:items-end shrink-0">
                            @if ($doc->match_status === 'unmatched' && $doc->ai_status === 'completed')
                                @php $candidates = $doc->match_candidates ?? []; @endphp

                                @if (!empty($candidates))
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-400 mb-1">Top match:</p>
                                        <p class="text-white">{{ $candidates[0]['label'] }}
                                            <span class="text-gray-400">({{ number_format($candidates[0]['score'] * 100) }}%)</span>
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('inbox.accept-match', $doc) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 transition">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                Accept
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('inbox.skip', $doc) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-white/5 border border-white/10 text-gray-400 text-xs font-medium rounded-md hover:bg-white/5 transition">
                                                Skip
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <p class="text-xs text-gray-400">No auto-match found</p>
                                @endif

                                {{-- Manual link --}}
                                <button type="button"
                                        class="manual-link-btn text-xs text-cyan-400 hover:text-cyan-300 underline"
                                        data-document-id="{{ $doc->id }}">
                                    Link manually&hellip;
                                </button>

                                {{-- Re-match --}}
                                <form method="POST" action="{{ route('inbox.rematch', $doc) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-gray-400 hover:text-gray-400 underline">Re-match</button>
                                </form>
                            @elseif (in_array($doc->match_status, ['matched', 'manual']))
                                @if ($doc->documentable)
                                    <p class="text-sm text-green-700 font-medium">
                                        Linked to: {{ class_basename($doc->documentable_type) }} #{{ $doc->documentable_id }}
                                    </p>
                                @endif
                            @elseif ($doc->ai_status === 'processing')
                                <span class="text-xs text-yellow-600">Analyzing&hellip;</span>
                            @elseif ($doc->ai_status === 'failed')
                                <span class="text-xs text-red-400">Analysis failed</span>
                            @endif

                            <div class="flex gap-2 mt-1">
                                <a href="{{ route('documents.detail', $doc) }}"
                                   class="text-xs text-gray-500 hover:text-cyan-400">View details</a>
                                <a href="{{ route('documents.show', $doc) }}" target="_blank"
                                   class="text-xs text-gray-500 hover:text-cyan-400">Download</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $documents->links() }}
        </div>
    @endif
</div>

{{-- Manual Link Modal --}}
<div id="manual-link-modal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50" onclick="closeManualLinkModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="surface-1-xl w-full max-w-md p-6 relative">
            <button type="button" onclick="closeManualLinkModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="text-lg font-semibold text-white mb-4">Link Document Manually</h3>

            <form id="manual-link-form" method="POST" action="">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Entity Type</label>
                        <select id="link-type" name="type" class="select-crystal w-full border-white/10 rounded-md text-sm focus:ring-cyan-500 focus:border-blue-500">
                            <option value="">Select type&hellip;</option>
                            <option value="invoice">Invoice</option>
                            <option value="expense">Expense</option>
                            <option value="receipt">Receipt</option>
                            <option value="warranty">Warranty</option>
                            <option value="work-order">Work Order</option>
                            <option value="estimate">Estimate</option>
                            <option value="customer">Customer</option>
                            <option value="service-request">Service Request</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Search</label>
                        <input type="text" id="link-search" placeholder="Search by number, name, or ID&hellip;"
                               class="w-full border-white/10 rounded-md text-sm focus:ring-cyan-500 focus:border-blue-500" autocomplete="off">
                        <input type="hidden" name="id" id="link-entity-id">
                    </div>

                    <div id="link-results" class="max-h-48 overflow-y-auto space-y-1 hidden"></div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" onclick="closeManualLinkModal()"
                            class="px-4 py-2 bg-white/5 border border-white/10 text-gray-300 text-sm rounded-md hover:bg-white/5">
                        Cancel
                    </button>
                    <button type="submit" id="link-submit-btn" disabled
                            class="px-4 py-2 btn-crystal text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh when documents are still processing
    @if ($isProcessing)
    setTimeout(function() { window.location.reload(); }, 10000);
    @endif

    // File input / drag-and-drop
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('inbox-files');
    const selectedFiles = document.getElementById('selected-files');
    const uploadBtn = document.getElementById('upload-btn');

    if (fileInput) {
        fileInput.addEventListener('change', updateFileList);
    }

    if (dropZone) {
        ['dragenter', 'dragover'].forEach(evt => {
            dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('border-blue-400', 'bg-cyan-500/10'); });
        });
        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('border-blue-400', 'bg-cyan-500/10'); });
        });
        dropZone.addEventListener('drop', e => {
            fileInput.files = e.dataTransfer.files;
            updateFileList();
        });
    }

    function updateFileList() {
        const files = fileInput.files;
        if (files.length === 0) {
            selectedFiles.classList.add('hidden');
            uploadBtn.disabled = true;
            return;
        }
        selectedFiles.classList.remove('hidden');
        selectedFiles.innerHTML = '';
        for (const f of files) {
            const div = document.createElement('div');
            div.className = 'text-sm text-gray-400 flex items-center gap-2';
            div.innerHTML = `<svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>` +
                f.name + ` <span class="text-gray-400">(${(f.size / 1024).toFixed(0)} KB)</span>`;
            selectedFiles.appendChild(div);
        }
        uploadBtn.disabled = false;
    }

    // Manual link modal
    const modal = document.getElementById('manual-link-modal');
    const linkForm = document.getElementById('manual-link-form');
    const linkType = document.getElementById('link-type');
    const linkSearch = document.getElementById('link-search');
    const linkResults = document.getElementById('link-results');
    const linkEntityId = document.getElementById('link-entity-id');
    const linkSubmitBtn = document.getElementById('link-submit-btn');
    let searchTimeout = null;

    document.querySelectorAll('.manual-link-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const docId = this.dataset.documentId;
            linkForm.action = '/inbox/' + docId + '/link';
            linkEntityId.value = '';
            linkSearch.value = '';
            linkType.value = '';
            linkResults.classList.add('hidden');
            linkResults.innerHTML = '';
            linkSubmitBtn.disabled = true;
            modal.classList.remove('hidden');
        });
    });

    linkSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        const type = linkType.value;
        if (q.length < 2 || !type) { linkResults.classList.add('hidden'); return; }

        searchTimeout = setTimeout(() => {
            fetch(`/inbox/search?type=${encodeURIComponent(type)}&query=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                linkResults.innerHTML = '';
                if (data.length === 0) {
                    linkResults.innerHTML = '<p class="text-sm text-gray-400 p-2">No results found</p>';
                } else {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'p-2 rounded cursor-pointer hover:bg-cyan-500/10 text-sm text-gray-300';
                        div.textContent = item.label;
                        div.addEventListener('click', () => {
                            linkEntityId.value = item.id;
                            linkSearch.value = item.label;
                            linkResults.classList.add('hidden');
                            linkSubmitBtn.disabled = false;
                        });
                        linkResults.appendChild(div);
                    });
                }
                linkResults.classList.remove('hidden');
            });
        }, 300);
    });

    linkType.addEventListener('change', function() {
        linkEntityId.value = '';
        linkSearch.value = '';
        linkResults.classList.add('hidden');
        linkSubmitBtn.disabled = true;
    });
});

function closeManualLinkModal() {
    document.getElementById('manual-link-modal').classList.add('hidden');
}
</script>
@endpush
@endsection
