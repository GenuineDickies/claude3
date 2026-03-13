@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Message Templates</h1>
            <p class="text-sm text-gray-500 mt-1">SMS templates for customer communications{{ \App\Models\Setting::getValue('telnyx_from_number', config('services.telnyx.from_number')) ? ' via Telnyx (' . \App\Models\Setting::getValue('telnyx_from_number', config('services.telnyx.from_number')) . ')' : '' }}.</p>
        </div>
        <a href="{{ route('message-templates.create') }}"
           class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-lg  transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Template
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Variable Reference Panel --}}
    <div x-data="{ open: false }" class="surface-1 mb-6">
        <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left">
            <div>
                <h2 class="text-base font-semibold text-white">Available Variables</h2>
                <p class="text-xs text-gray-500 mt-0.5">Use <code class="bg-white/5 px-1 py-0.5 rounded-sm text-xs">@{{ variable_name }}</code> in template bodies to insert dynamic data.</p>
            </div>
            <svg :class="{ 'rotate-180': open }" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="px-6 pb-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @php $vars = \App\Models\MessageTemplate::availableVariables(); @endphp
                @foreach($vars as $key => $def)
                    <div class="flex items-center gap-2 p-2 bg-white/5 rounded-sm">
                        <code class="text-xs bg-cyan-500/10 text-cyan-400 px-1.5 py-0.5 rounded-sm font-mono whitespace-nowrap">@{{ {{ e($key) }} }}</code>
                        <span class="text-xs text-gray-400 truncate">{{ $def['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Templates by Category --}}
    @forelse($categories as $catKey => $catLabel)
        @if(isset($templates[$catKey]) && $templates[$catKey]->isNotEmpty())
            <div class="mb-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $catLabel }}</h2>
                <div class="surface-1 divide-y divide-gray-100">
                    @foreach($templates[$catKey] as $template)
                        <div class="flex items-start justify-between gap-4 px-6 py-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-8 h-5 rounded-sm bg-white/10 text-[10px] font-bold text-gray-400 font-mono shrink-0" title="Template ID">{{ $template->id }}</span>
                                    <a href="{{ route('message-templates.show', $template) }}"
                                       class="text-sm font-medium text-white hover:text-cyan-400">
                                        {{ $template->name }}
                                    </a>
                                    @if(!$template->is_active)
                                        <span class="inline-block px-1.5 py-0.5 text-xs font-medium bg-white/5 text-gray-500 rounded-sm">Inactive</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1 truncate">{{ $template->body }}</p>
                                @if($template->variables && count($template->variables))
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($template->variables as $var)
                                            <span class="inline-block text-[10px] bg-cyan-500/10 text-cyan-400 px-1.5 py-0.5 rounded-sm font-mono">{{ $var }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('message-templates.edit', $template) }}"
                                   class="text-sm text-gray-400 hover:text-cyan-400" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('message-templates.show', $template) }}"
                                   class="text-sm text-gray-400 hover:text-green-400" title="Preview">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @empty
        <div class="surface-1 p-8 text-center">
            <p class="text-gray-500">No templates yet. Create one to get started.</p>
        </div>
    @endforelse
</div>
@endsection
