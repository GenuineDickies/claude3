@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <a href="{{ route('leads.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Inbound Queue
    </a>

    <div>
        <h1 class="text-2xl font-bold text-white">New Inbound Request</h1>
        <p class="text-sm text-gray-500 mt-1">Capture details from inbound calls, ads, web forms, or referrals.</p>
    </div>

    <form method="POST" action="{{ route('leads.store') }}" class="surface-1 p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-300 mb-1">First Name</label>
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-300 mb-1">Last Name</label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
            <div>
                <label for="stage" class="block text-sm font-medium text-gray-300 mb-1">Stage</label>
                <select id="stage" name="stage" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                    @foreach ($stageOptions as $stageKey => $stageLabel)
                        <option value="{{ $stageKey }}" @selected(old('stage', \App\Models\Lead::STAGE_NEW) === $stageKey)>{{ $stageLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="source" class="block text-sm font-medium text-gray-300 mb-1">Source</label>
                <input id="source" name="source" type="text" value="{{ old('source', 'inbound_call') }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="service_needed" class="block text-sm font-medium text-gray-300 mb-1">Service Needed</label>
                <input id="service_needed" name="service_needed" type="text" value="{{ old('service_needed') }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" placeholder="Towing, lockout, jump start">
            </div>
            <div>
                <label for="location" class="block text-sm font-medium text-gray-300 mb-1">Approximate Location</label>
                <input id="location" name="location" type="text" value="{{ old('location') }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" placeholder="City or cross streets">
            </div>
            <div>
                <label for="estimated_value" class="block text-sm font-medium text-gray-300 mb-1">Estimated Value</label>
                <input id="estimated_value" name="estimated_value" type="number" step="0.01" min="0" value="{{ old('estimated_value') }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" placeholder="0.00">
            </div>
        </div>

        <div>
            <label for="assigned_user_id" class="block text-sm font-medium text-gray-300 mb-1">Assigned To</label>
            <select id="assigned_user_id" name="assigned_user_id" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                <option value="">Unassigned</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('assigned_user_id') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
            <textarea id="notes" name="notes" rows="4" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" placeholder="Call notes, constraints, or follow-up details">{{ old('notes') }}</textarea>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-crystal px-5 py-2 text-sm font-semibold">Create Inbound Request</button>
            <a href="{{ route('leads.index') }}" class="btn-crystal-secondary px-5 py-2 text-sm font-semibold">Cancel</a>
        </div>
    </form>
</div>
@endsection
