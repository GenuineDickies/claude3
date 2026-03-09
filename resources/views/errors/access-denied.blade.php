@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="rounded-2xl border border-red-200 bg-white p-8 shadow-sm">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86l-7.188 12.45A1.5 1.5 0 0 0 4.41 18.56h14.38a1.5 1.5 0 0 0 1.308-2.25L12.91 3.86a1.5 1.5 0 0 0-2.62 0Z" />
            </svg>
        </div>
        <div class="mt-5 text-center">
            <h1 class="text-2xl font-bold text-gray-900">Access Denied</h1>
            <p class="mt-2 text-sm text-gray-500">Your current role assignments do not allow access to this page.</p>
            @if (request('page'))
                <p class="mt-4 rounded-lg bg-gray-50 px-3 py-2 font-mono text-xs text-gray-600">{{ request('page') }}</p>
            @endif
        </div>
        <div class="mt-6 flex justify-center gap-3">
            <a href="{{ route('dashboard') }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Go to dashboard</a>
            <a href="{{ route('profile.edit') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Open profile</a>
        </div>
    </div>
</div>
@endsection