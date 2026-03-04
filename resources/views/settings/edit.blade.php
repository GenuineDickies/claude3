@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure API keys, SMS credentials, and other options. Values are stored in the database — not in config files — so each deployment can manage its own credentials.</p>
    </div>

    {{-- Status overview --}}
    <div class="bg-white rounded-lg shadow-xs mb-6 px-6 py-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Configuration Status</h2>
        <div class="grid grid-cols-3 gap-2">
            @foreach($definitions as $groupKey => $section)
                @foreach($section['fields'] as $key => $field)
                    @php
                        $isConfigured = isset($values[$key]['raw']) && $values[$key]['raw'] !== null && $values[$key]['raw'] !== '';
                    @endphp
                    <div class="flex flex-col items-center justify-center text-center rounded-lg px-2 py-3 ring-1
                        {{ $isConfigured ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-red-50 text-red-600 ring-red-200' }}">
                        @if($isConfigured)
                            <svg class="w-5 h-5 mb-1.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        @else
                            <svg class="w-5 h-5 mb-1.5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        @endif
                        <span class="text-[11px] font-medium leading-tight">{{ $field['label'] }}</span>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @foreach($definitions as $groupKey => $section)
        <div class="bg-white rounded-lg shadow-xs mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">{{ $section['label'] }}</h2>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($section['fields'] as $key => $field)
                    @if(!empty($field['hidden']))
                        @continue
                    @endif
                    @php
                        $currentValue = $values[$key]['raw'] ?? null;
                        $isConfigured = $currentValue !== null && $currentValue !== '';
                        $displayValue = $field['encrypted']
                            ? ($values[$key]['masked'] ?? '')
                            : ($currentValue ?? '');
                        $inputType = ($field['type'] ?? '') === 'number' ? 'number' : 'text';
                    @endphp

                    @if(($field['type'] ?? '') === 'approval_mode')
                        {{-- Estimate Approval Mode — three-way radio --}}
                        @php
                            $mode = $currentValue ?? 'none';
                            $thresholdValue = $values['estimate_signature_threshold']['raw'] ?? '';
                        @endphp
                        <form action="{{ route('settings.update-approval-mode') }}" method="POST" autocomplete="off" class="px-6 py-5">
                            @csrf
                            @method('PUT')

                            <div class="mb-1">
                                <span class="block text-sm font-medium text-gray-800">{{ $field['label'] }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">{{ $field['help'] }}</p>

                            <div class="space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="approval_mode" value="all"
                                        {{ $mode === 'all' ? 'checked' : '' }}
                                        class="text-blue-600 focus:ring-blue-500"
                                        onchange="document.getElementById('threshold_input').classList.add('hidden')">
                                    <span class="text-sm text-gray-700">All estimates require approval</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="approval_mode" value="none"
                                        {{ $mode === 'none' ? 'checked' : '' }}
                                        class="text-blue-600 focus:ring-blue-500"
                                        onchange="document.getElementById('threshold_input').classList.add('hidden')">
                                    <span class="text-sm text-gray-700">No estimates require approval</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="approval_mode" value="threshold"
                                        {{ $mode === 'threshold' ? 'checked' : '' }}
                                        class="text-blue-600 focus:ring-blue-500"
                                        onchange="document.getElementById('threshold_input').classList.remove('hidden')">
                                    <span class="text-sm text-gray-700">Estimates require approval if they are more than</span>
                                </label>

                                <div id="threshold_input" class="ml-8 {{ $mode === 'threshold' ? '' : 'hidden' }}">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-600">$</span>
                                        <input type="number" name="threshold_amount" value="{{ $thresholdValue }}"
                                            placeholder="200.00" step="0.01" min="0.01"
                                            class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Save
                                </button>
                            </div>
                        </form>
                    @else
                    <form action="{{ route('settings.update-single', $key) }}" method="POST" autocomplete="off" class="px-6 py-5">
                        @csrf
                        @method('PUT')

                        <div class="flex items-center gap-2 mb-1">
                            <label for="settings_{{ $key }}" class="block text-sm font-medium text-gray-800">
                                {{ $field['label'] }}
                            </label>

                            @if($isConfigured)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-sm text-[10px] font-semibold bg-green-100 text-green-700 uppercase tracking-wide">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-sm text-[10px] font-semibold bg-red-100 text-red-600 uppercase tracking-wide">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    Not configured
                                </span>
                            @endif

                            @if($field['encrypted'])
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-sm text-[10px] font-semibold bg-amber-100 text-amber-700 uppercase tracking-wide">Encrypted</span>
                            @endif
                        </div>

                        <p class="text-xs text-gray-500 mb-2">{{ $field['help'] }}</p>

                        @if(!empty($field['how_to']))
                            <details class="mb-3">
                                <summary class="text-xs text-blue-600 cursor-pointer hover:text-blue-800 font-medium">Where do I get this?</summary>
                                <div class="mt-1.5 text-xs text-gray-600 bg-blue-50 border border-blue-100 rounded-md px-3 py-2 leading-relaxed">
                                    {!! $field['how_to'] !!}
                                </div>
                            </details>
                        @endif

                        <div class="flex gap-2">
                            <input
                                type="{{ $inputType }}"
                                id="settings_{{ $key }}"
                                name="value"
                                value="{{ $displayValue }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="flex-1 border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                    {{ $isConfigured ? 'border-green-300 bg-green-50/30' : 'border-gray-300' }}
                                    {{ $field['encrypted'] && $currentValue ? 'font-mono text-gray-400' : '' }}"
                                @if($field['encrypted'] && $currentValue)
                                    onfocus="if(this.value.match(/^[•]+$/)){this.value='';this.classList.remove('text-gray-400');}"
                                @endif
                            >
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors shrink-0">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $isConfigured ? 'Update' : 'Save' }}
                            </button>
                        </div>

                        @if($field['encrypted'] && $currentValue)
                            <p class="text-[11px] text-gray-400 mt-1">Value is stored encrypted. Click the field and type a new value to replace it, or leave the dots to keep the current value.</p>
                        @endif
                    </form>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="mb-8">
        <div class="flex gap-4">
            <a href="/" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to dashboard</a>
            <a href="{{ route('settings.tax-rates') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Manage State Tax Rates &rarr;</a>
            <a href="{{ route('settings.api-monitor.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Manage API Monitoring &rarr;</a>
        </div>
    </div>
</div>
@endsection
