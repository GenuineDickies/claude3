@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto surface-1 p-6">
    <h1 class="text-xl font-bold mb-4">Test Location Request</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-sm mb-4">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-sm mb-4">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-sm mb-4">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="bg-blue-100 border border-blue-400 text-cyan-400 px-4 py-3 rounded-sm mb-4">{{ session('info') }}</div>
    @endif

    <form method="POST" action="/test-location" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name') }}"
                   placeholder="Jane"
                   class="w-full border border-white/10 rounded-md px-3 py-2 focus:ring-cyan-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Phone Number</label>
            <input type="text" name="phone" value="{{ old('phone') }}"
                   placeholder="5551234567"
                   class="w-full border border-white/10 rounded-md px-3 py-2 focus:ring-cyan-500 focus:border-blue-500">
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="force_consent" id="force_consent" value="1" class="rounded-sm border-white/10">
            <label for="force_consent" class="text-sm text-gray-400">Skip consent check (grant consent automatically for testing)</label>
        </div>
        <button type="submit"
                class="w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-md  transition">
            Send Location Request SMS
        </button>
    </form>
</div>
@endsection
