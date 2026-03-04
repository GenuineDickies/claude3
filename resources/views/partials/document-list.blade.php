{{--
    Reusable document list + upload partial.

    Required variables:
    - $documents  (Collection of Document models)
    - $uploadUrl  (string — form action URL for uploading)

    Optional:
    - $defaultCategory (string — pre-selected category, default 'other')
--}}

@php $defaultCategory = $defaultCategory ?? 'other'; @endphp

{{-- Upload form --}}
<form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data"
      class="flex flex-wrap items-end gap-3 mb-4 pb-4 border-b border-gray-200">
    @csrf
    <div class="flex-1 min-w-[200px]">
        <label for="file" class="block text-xs font-medium text-gray-500 mb-1">File</label>
        <input type="file" name="file" id="file" required
               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
               class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="category" class="block text-xs font-medium text-gray-500 mb-1">Category</label>
        <select name="category" id="category"
                class="rounded-md border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
            @foreach (\App\Models\Document::CATEGORY_LABELS as $key => $label)
                <option value="{{ $key }}" {{ $key === $defaultCategory ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
        Upload
    </button>
</form>

{{-- Document list --}}
@if ($documents->isEmpty())
    <p class="text-sm text-gray-500">No documents attached.</p>
@else
    <ul class="divide-y divide-gray-100">
        @foreach ($documents as $doc)
        <li class="flex items-center justify-between py-2">
            <div class="flex items-center gap-3 min-w-0">
                {{-- AI status indicator --}}
                @php
                    $dotColor = match($doc->ai_status) {
                        'pending'    => 'bg-gray-400',
                        'processing' => 'bg-blue-400 animate-pulse',
                        'completed'  => 'bg-green-400',
                        'failed'     => 'bg-red-400',
                        default      => 'bg-gray-300',
                    };
                @endphp
                <span class="w-2 h-2 rounded-full {{ $dotColor }} shrink-0" title="AI: {{ ucfirst($doc->ai_status) }}"></span>

                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                <div class="min-w-0">
                    <a href="{{ route('documents.detail', $doc) }}"
                       class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block">{{ $doc->original_filename }}</a>
                    <p class="text-xs text-gray-400">
                        {{ \App\Models\Document::CATEGORY_LABELS[$doc->category] ?? $doc->category }}
                        &middot; {{ $doc->humanFileSize() }}
                        @if ($doc->uploader) &middot; {{ $doc->uploader->name }} @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('documents.show', $doc) }}" target="_blank"
                   class="text-gray-400 hover:text-gray-600 p-1" title="Download">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </a>
                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                      onsubmit="return confirm('Delete this document?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-400 hover:text-red-600 p-1" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </form>
            </div>
        </li>
        @endforeach
    </ul>
@endif
