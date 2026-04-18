{{--
  Customer Show Page — customers.show
  Feature preservation notes:
    - Back link to customers index
    - Header with customer name and View service requests link (filtered by phone)
    - Session success flash
    - Two-column form (PUT to customers.update) with @csrf and @method('PUT')
    - Customer Details card (first_name, last_name, phone inputs with @error blocks, is_active checkbox)
    - Notification Preferences card (status_updates, location_requests, signature_requests, marketing checkboxes using DEFAULT_NOTIFICATION_PREFERENCES)
    - Persistent Vehicles table (vehicle name, plate, VIN)
    - Save Customer submit button
    - Sidebar: SMS Consent panel (status, sms_consent_at, sms_opt_out_at)
    - Sidebar: Recent Service Requests list with link per service request
    - Sidebar: Recent Communication (Messages and Correspondence sub-lists)
  Layout changes only:
    - Outer container already wider than max-w-3xl (max-w-6xl preserved)
    - Vertical spacing left as space-y-6 to preserve form column rhythm
    - All Alpine state, forms, routes, and PHP logic kept intact
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <a href="{{ route('customers.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Customers
    </a>

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $customer->first_name }} {{ $customer->last_name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Customer record, notification preferences, and recent history.</p>
        </div>
        <a href="{{ route('service-requests.index', ['search' => $customer->phone]) }}" class="btn-crystal px-4 py-2 text-sm font-semibold">View service requests</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="grid gap-6 lg:grid-cols-[minmax(0,2fr),minmax(320px,1fr)]">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">Customer Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-300 mb-1">First Name</label>
                        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $customer->first_name) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                        @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-300 mb-1">Last Name</label>
                        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $customer->last_name) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                        @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                        @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-3 rounded-lg border border-white/10 px-4 py-3 text-sm text-gray-300">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-white/10 text-cyan-400 focus:ring-cyan-500" @checked(old('is_active', $customer->is_active))>
                            <span>Customer record is active</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">Notification Preferences</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $prefs = old('notification_preferences', $customer->notification_preferences ?? \App\Models\Customer::DEFAULT_NOTIFICATION_PREFERENCES);
                    @endphp
                    @foreach ([
                        'status_updates' => 'Status updates',
                        'location_requests' => 'Location requests',
                        'signature_requests' => 'Signature requests',
                        'marketing' => 'Marketing',
                    ] as $preferenceKey => $preferenceLabel)
                        <label class="flex items-center gap-3 rounded-lg border border-white/10 px-4 py-3 text-sm text-gray-300">
                            <input type="checkbox" name="notification_preferences[{{ $preferenceKey }}]" value="1" class="rounded border-white/10 text-cyan-400 focus:ring-cyan-500" @checked(data_get($prefs, $preferenceKey, true))>
                            <span>{{ $preferenceLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-gray-500">These settings control whether the customer receives message types after consent already exists.</p>
            </div>

            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">Persistent Vehicles</h2>
                @if ($customer->vehicles->isEmpty())
                    <p class="text-sm text-gray-500">No persistent vehicle records are attached yet. Vehicle records are created once plate or VIN is available, typically during technician or invoice workflows.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-crystal min-w-full divide-y divide-white/5">
                            <thead class="bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Vehicle</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Plate</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">VIN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($customer->vehicles as $vehicle)
                                    <tr>
                                        <td class="px-4 py-4 text-sm text-white">{{ $vehicle->displayName() }}</td>
                                        <td class="px-4 py-4 text-sm font-mono text-gray-300">{{ $vehicle->license_plate ?: '—' }}</td>
                                        <td class="px-4 py-4 text-sm font-mono text-gray-300">{{ $vehicle->vin ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-crystal px-5 py-2 text-sm font-semibold">Save Customer</button>
            </div>
        </div>

        <div class="space-y-6">
            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">SMS Consent</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-gray-500">Current Status</span>
                        @if ($customer->hasSmsConsent())
                            <span class="font-medium text-green-400">Opted in</span>
                        @else
                            <span class="font-medium text-red-400">No active consent</span>
                        @endif
                    </div>
                    <div>
                        <span class="block text-gray-500">Consent Recorded</span>
                        <span class="font-medium text-gray-300">{{ $customer->sms_consent_at?->format('M j, Y g:i A') ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500">Opted Out</span>
                        <span class="font-medium text-gray-300">{{ $customer->sms_opt_out_at?->format('M j, Y g:i A') ?: '—' }}</span>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-500">Customer consent remains a verbal-consent intake workflow. This screen shows status but does not create a self-consent flow.</p>
            </div>

            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">Recent Service Requests</h2>
                @if ($customer->serviceRequests->isEmpty())
                    <p class="text-sm text-gray-500">No service requests yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($customer->serviceRequests as $serviceRequest)
                            <a href="{{ route('service-requests.show', $serviceRequest) }}" class="block rounded-lg border border-white/10 px-4 py-3 hover:bg-white/5">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-white">Service Request #{{ $serviceRequest->id }}</span>
                                    <span class="text-xs text-gray-500">{{ $serviceRequest->created_at->format('M j, Y') }}</span>
                                </div>
                                <div class="mt-1 text-sm text-gray-400">{{ $serviceRequest->catalogItem?->name ?: 'No service type' }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $serviceRequest->status }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="surface-1 p-6">
                <h2 class="text-lg font-semibold text-gray-300 mb-4">Recent Communication</h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Messages</h3>
                        @if ($customer->messages->isEmpty())
                            <p class="mt-2 text-sm text-gray-500">No SMS history recorded.</p>
                        @else
                            <div class="mt-2 space-y-2">
                                @foreach ($customer->messages as $message)
                                    <div class="rounded-lg border border-white/10 px-3 py-2 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-medium text-white">{{ ucfirst($message->direction) }}</span>
                                            <span class="text-xs text-gray-500">{{ $message->created_at?->format('M j, Y g:i A') }}</span>
                                        </div>
                                        <p class="mt-1 text-gray-400">{{ $message->body }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Correspondence</h3>
                        @if ($customer->correspondences->isEmpty())
                            <p class="mt-2 text-sm text-gray-500">No correspondence logged.</p>
                        @else
                            <div class="mt-2 space-y-2">
                                @foreach ($customer->correspondences as $correspondence)
                                    <div class="rounded-lg border border-white/10 px-3 py-2 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-medium text-white">{{ ucfirst(str_replace('_', ' ', $correspondence->channel)) }}</span>
                                            <span class="text-xs text-gray-500">{{ $correspondence->logged_at?->format('M j, Y g:i A') }}</span>
                                        </div>
                                        <p class="mt-1 text-gray-400">{{ $correspondence->subject ?: $correspondence->body ?: 'No subject' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection