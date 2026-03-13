@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Service Catalog</h1>
            <p class="text-sm text-gray-500 mt-1">Manage the services you offer.</p>
        </div>
        <a href="{{ route('catalog.items.create') }}"
           class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-lg  transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Service
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($services->isEmpty())
        <div class="surface-1 p-8 text-center">
            <p class="text-gray-500">No services yet. Add one to get started.</p>
        </div>
    @else
        <div class="surface-1 divide-y divide-gray-100">
            @foreach($services as $service)
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-white">{{ $service->name }}</span>
                            @if(!$service->is_active)
                                <span class="text-xs bg-white/5 text-gray-500 px-1.5 py-0.5 rounded-sm">Inactive</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm font-semibold text-gray-300">${{ number_format($service->base_cost, 2) }}</span>
                            <span class="text-xs text-gray-500">/ {{ $service->unit }}</span>
                            <span @class([
                                'text-xs px-1.5 py-0.5 rounded-sm font-medium',
                                'bg-green-500/10 text-green-700' => $service->pricing_type === 'fixed',
                                'bg-purple-50 text-purple-700' => $service->pricing_type === 'variable',
                            ])>
                                {{ ucfirst($service->pricing_type) }}
                            </span>
                        </div>
                        @if($service->description)
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ $service->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('catalog.items.edit', $service) }}"
                           class="text-sm text-gray-400 hover:text-cyan-400" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
