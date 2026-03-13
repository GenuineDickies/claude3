@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('settings.edit') }}" class="hover:text-cyan-400">Settings</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-300 font-medium">State Tax Rates</span>
        </div>
        <h1 class="text-2xl font-bold text-white">State Sales Tax Rates</h1>
        <p class="text-sm text-gray-500 mt-1">Set the sales tax rate for each US state. These rates are used when creating estimates. Leave blank for states where you don't operate.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('settings.tax-rates.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="surface-1 overflow-hidden mb-6">
            <table class="w-full text-sm">
                <thead class="bg-white/5">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">State</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Tax Rate (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($stateList as $code => $name)
                        @php $currentRate = $rates[$code]->tax_rate ?? ''; @endphp
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-2">
                                <span class="font-medium text-white">{{ $name }}</span>
                                <span class="text-xs text-gray-400 ml-1">({{ $code }})</span>
                            </td>
                            <td class="px-4 py-2">
                                <input type="number"
                                       name="rates[{{ $code }}]"
                                       value="{{ $currentRate !== '' ? ($currentRate + 0) : '' }}"
                                       step="0.0001"
                                       min="0"
                                       max="100"
                                       placeholder="—"
                                       class="w-full border border-white/10 rounded-md px-3 py-1.5 text-sm focus:ring-2 focus:ring-cyan-500 focus:border-blue-500
                                              {{ $currentRate !== '' ? 'bg-green-500/10/30 border-green-300' : '' }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center mb-8">
            <a href="{{ route('settings.edit') }}" class="text-sm text-gray-500 hover:text-gray-300">&larr; Back to Settings</a>
            <button type="submit"
                    class="bg-blue-600 text-white font-medium px-6 py-2.5 rounded-md  transition">
                Save Tax Rates
            </button>
        </div>
    </form>
</div>
@endsection
