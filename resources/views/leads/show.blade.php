@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <a href="{{ route('leads.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Inbound Queue
    </a>

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $lead->first_name }} {{ $lead->last_name }}</h1>
            <p class="text-sm text-gray-500 mt-1">Inbound request details and intake progression.</p>
        </div>
        <form method="POST" action="{{ route('leads.start-intake', $lead) }}">
            @csrf
                <button type="submit" class="btn-crystal px-4 py-2 text-sm font-semibold">Convert to Intake Ticket</button>
        </form>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('leads.update', $lead) }}" class="grid gap-6 lg:grid-cols-[minmax(0,2fr),minmax(320px,1fr)]">
        @csrf
        @method('PUT')

        <div class="surface-1 p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-300 mb-1">First Name</label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $lead->first_name) }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-300 mb-1">Last Name</label>
                    <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $lead->last_name) }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $lead->phone) }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $lead->email) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
                <div>
                    <label for="stage" class="block text-sm font-medium text-gray-300 mb-1">Stage</label>
                    <select id="stage" name="stage" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                        @foreach ($stageOptions as $stageKey => $stageLabel)
                            <option value="{{ $stageKey }}" @selected(old('stage', $lead->stage) === $stageKey)>{{ $stageLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="source" class="block text-sm font-medium text-gray-300 mb-1">Source</label>
                    <input id="source" name="source" type="text" value="{{ old('source', $lead->source) }}" required class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="service_needed" class="block text-sm font-medium text-gray-300 mb-1">Service Needed</label>
                    <input id="service_needed" name="service_needed" type="text" value="{{ old('service_needed', $lead->service_needed) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-300 mb-1">Approximate Location</label>
                    <input id="location" name="location" type="text" value="{{ old('location', $lead->location) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
                <div>
                    <label for="estimated_value" class="block text-sm font-medium text-gray-300 mb-1">Estimated Value</label>
                    <input id="estimated_value" name="estimated_value" type="number" step="0.01" min="0" value="{{ old('estimated_value', $lead->estimated_value) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                </div>
            </div>

            <div>
                <label for="assigned_user_id" class="block text-sm font-medium text-gray-300 mb-1">Assigned To</label>
                <select id="assigned_user_id" name="assigned_user_id" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $lead->assigned_user_id) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="5" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">{{ old('notes', $lead->notes) }}</textarea>
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

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-crystal px-5 py-2 text-sm font-semibold">Save Request</button>
                <a href="{{ route('service-requests.create', ['lead_id' => $lead->id, 'first_name' => $lead->first_name, 'last_name' => $lead->last_name, 'phone' => $lead->phone, 'street_address' => $lead->location, 'notes' => $lead->notes]) }}" class="btn-crystal-secondary px-5 py-2 text-sm font-semibold">Open Intake Form</a>
            </div>
        </div>

        <div class="space-y-6">
            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">Conversion Status</h2>
                @if ($lead->converted_at)
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-300">Converted {{ $lead->converted_at->diffForHumans() }}.</p>
                        <p class="text-gray-500">Customer ID: {{ $lead->converted_customer_id ?? 'N/A' }}</p>
                        @if ($lead->convertedServiceRequest)
                            <a href="{{ route('service-requests.show', $lead->convertedServiceRequest) }}" class="text-cyan-400 hover:text-cyan-300">Open Ticket #{{ $lead->converted_service_request_id }}</a>
                        @else
                            <p class="text-gray-500">Ticket ID: {{ $lead->converted_service_request_id ?? 'N/A' }}</p>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-500">Not converted yet. Use intake action to create the ticket.</p>
                @endif
            </div>

            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">Delete Request</h2>
                <p class="text-sm text-gray-500 mb-4">Remove this inbound request if it was created by mistake or is no longer relevant.</p>
                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Delete this inbound request?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-md border border-red-500/40 px-3 py-2 text-sm font-medium text-red-300 hover:bg-red-500/10">Delete Request</button>
                </form>
            </div>
        </div>
    </form>
</div>
@endsection
