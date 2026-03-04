@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Back link --}}
    <a href="{{ url()->previous() }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back
    </a>

    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 break-all">{{ $document->original_filename }}</h1>
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
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Download
                </a>
                <form method="POST" action="{{ route('documents.destroy', $document) }}"
                      onsubmit="return confirm('Delete this document?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-md hover:bg-red-50 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- AI Analysis Status --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">AI Analysis</h2>
            <div class="flex items-center gap-3">
                @php
                    $statusColors = match($document->ai_status) {
                        'pending'    => 'bg-gray-100 text-gray-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'completed'  => 'bg-green-100 text-green-700',
                        'failed'     => 'bg-red-100 text-red-700',
                        default      => 'bg-gray-100 text-gray-700',
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
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                    Retry Analysis
                </button>
            </form>
        @elseif ($document->isAiPending())
            <p class="text-sm text-gray-500">Analysis is pending or in progress. This page will show results once processing completes.</p>
        @elseif ($document->isAiCompleted())

            {{-- Category comparison --}}
            @if ($document->ai_suggested_category && $document->ai_suggested_category !== $document->category)
                <div class="rounded-md bg-amber-50 border border-amber-200 p-4 mb-4">
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
                    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">Summary</h3>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $document->ai_summary }}</p>
                </div>
            @endif

            {{-- Tags --}}
            @if (! empty($document->ai_tags))
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">Tags</h3>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($document->ai_tags as $tag)
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Extracted Data --}}
            @if (! empty($document->ai_extracted_data))
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">Extracted Data</h3>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($document->ai_extracted_data as $key => $value)
                                    <tr>
                                        <td class="px-4 py-2.5 font-medium text-gray-600 bg-gray-50 whitespace-nowrap w-1/3">
                                            {{ str_replace('_', ' ', ucfirst($key)) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-800">
                                            @if (is_array($value))
                                                <div class="space-y-1">
                                                    @foreach ($value as $item)
                                                        @if (is_array($item))
                                                            <div class="text-xs bg-gray-50 rounded p-2 font-mono">
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
            <div class="pt-4 border-t border-gray-200">
                <form method="POST" action="{{ route('documents.reanalyze', $document) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        Re-analyze
                    </button>
                </form>
            </div>

            @if ($document->ai_processed_at)
                <p class="text-xs text-gray-400 mt-3">Analyzed {{ $document->ai_processed_at->diffForHumans() }}</p>
            @endif
        @endif
    </div>

</div>
@endsection
