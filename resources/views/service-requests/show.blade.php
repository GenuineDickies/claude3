@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('service-requests.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        All Tickets
    </a>

    {{-- Header --}}
    <div class="surface-1 p-6" x-data="{ assigning: false, showNotes: false }">
        <div class="flex justify-between items-start gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    Service Request #{{ $serviceRequest->id }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">Created {{ $serviceRequest->created_at->format('M j, Y g:i A') }}</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                {{-- Assign Technician --}}
                @if ($serviceRequest->assignedTechnician)
                    <span class="inline-flex items-center gap-1.5 text-sm text-gray-200 rounded-md border border-white/10 bg-white/5 px-3 py-1.5 shadow-sm">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $serviceRequest->assignedTechnician->name }}
                    </span>
                    <button type="button" @click="assigning = true" x-show="!assigning" class="text-sm text-gray-500 hover:text-gray-300 underline">Change</button>
                @else
                    <button type="button" @click="assigning = true" x-show="!assigning"
                            class="inline-flex items-center px-3 py-2 min-h-[44px] btn-crystal text-sm font-medium rounded-md">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Assign Technician
                    </button>
                @endif

                {{-- Inline assign form --}}
                <form method="POST" action="{{ route('service-requests.assign-technician', $serviceRequest) }}"
                      x-show="assigning" x-cloak class="inline-flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="assigned_user_id" required
                            class="text-sm border-white/10 rounded-md shadow-sm input-crystal">
                        <option value="">Select…</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected($serviceRequest->assigned_user_id == $tech->id)>{{ $tech->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center px-4 py-2 min-h-[44px] btn-crystal-success text-sm font-semibold rounded-md">Save</button>
                    <button type="button" @click="assigning = false" class="text-sm text-gray-500 hover:text-gray-300 underline">Cancel</button>
                </form>

                <x-status-badge :status="$serviceRequest->status" class="px-3 py-1 text-sm" />
            </div>
        </div>

        {{-- Status transition controls --}}
        @if (! in_array($serviceRequest->status, \App\Models\ServiceRequest::TERMINAL_STATUSES))
        <div class="mt-4 border-t border-white/10 pt-4">
            @php
                $nextStatus = $serviceRequest->nextStatus();
                $canAdvance = $nextStatus ? $serviceRequest->canTransitionTo($nextStatus) : false;
                $advanceBlockedReason = $nextStatus === 'dispatched' ? $serviceRequest->dispatchBlockedReason() : null;
            @endphp
            <div class="flex flex-wrap items-center gap-3">
                @if ($nextStatus)
                <form method="POST" action="{{ route('service-requests.update', $serviceRequest) }}" class="inline" x-ref="advanceForm">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $nextStatus }}">
                    <input type="hidden" name="notes" x-bind:value="$refs.notesField?.value || ''">
                    <input type="hidden" name="notify_customer" x-bind:value="$refs.notifyCheckbox?.checked ? '1' : '0'">
                    <button type="submit"
                            @disabled(! $canAdvance)
                            aria-disabled="{{ $canAdvance ? 'false' : 'true' }}"
                            class="inline-flex items-center px-4 py-2 min-h-[44px] text-sm font-semibold rounded-md transition {{ $canAdvance ? 'btn-crystal-success' : 'bg-white/10 text-gray-500 cursor-not-allowed border border-white/10' }}">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        Mark as {{ \App\Models\ServiceRequest::STATUS_LABELS[$nextStatus] }}
                    </button>
                </form>
                @endif

                <form method="POST" action="{{ route('service-requests.update', $serviceRequest) }}" class="inline" x-ref="cancelForm">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="cancelled">
                    <input type="hidden" name="notes" x-bind:value="$refs.notesField?.value || ''">
                    <input type="hidden" name="notify_customer" x-bind:value="$refs.notifyCheckbox?.checked ? '1' : '0'">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 min-h-[44px] border border-red-500/30 text-red-400 text-sm font-semibold rounded-md hover:bg-red-500/10 transition"
                            onclick="return confirm('Cancel this service request?')">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Cancel
                    </button>
                </form>

                <button type="button"
                    @click="showNotes = !showNotes"
                    class="inline-flex items-center px-4 py-2 min-h-[44px] btn-crystal-secondary text-sm font-semibold rounded-md">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Add note
                </button>
            </div>

            @if (! $canAdvance && $advanceBlockedReason)
            <p class="mt-3 text-sm text-amber-700">{{ $advanceBlockedReason }}</p>
            @endif

            <div x-show="showNotes" x-cloak class="mt-3 space-y-2">
                <textarea x-ref="notesField"
                          rows="2"
                          maxlength="1000"
                          placeholder="Optional note about this status change…"
                          class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal resize-none"></textarea>
                <label class="flex items-center gap-2 text-sm text-gray-400">
                    <input type="checkbox" x-ref="notifyCheckbox" value="1"
                           class="rounded border-white/10 text-cyan-400 focus:ring-cyan-500"
                           {{ $serviceRequest->customer?->hasSmsConsent() ? 'checked' : '' }}>
                    Notify customer via SMS
                </label>
            </div>
        </div>
        @endif
    </div>

    {{-- Workflow Progress Tracker --}}
    @php
        $isCancelled = $serviceRequest->status === 'cancelled';
        $hasEstimate = $serviceRequest->estimates->isNotEmpty();
        $hasWorkOrder = $serviceRequest->workOrders->isNotEmpty();
        $hasInvoice = $serviceRequest->invoices->isNotEmpty();
        $isPaid = $serviceRequest->paymentStatus() === 'paid';
        $isComplete = $serviceRequest->status === 'completed';

        $stages = [
            ['label' => 'Create Estimate', 'done' => $hasEstimate],
            ['label' => 'Create Work Order', 'done' => $hasWorkOrder],
            ['label' => 'Create Invoice', 'done' => $hasInvoice],
            ['label' => 'Collect Payment', 'done' => $isPaid],
            ['label' => 'Complete', 'done' => $isComplete],
        ];

        // Find the first incomplete stage (yellow / current)
        $currentIndex = null;
        if (! $isCancelled) {
            foreach ($stages as $i => $s) {
                if (! $s['done']) { $currentIndex = $i; break; }
            }
        }
    @endphp
    <div class="surface-1 p-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Workflow Progress</h2>
        <div class="flex items-center">
            @foreach ($stages as $i => $stage)
                @php
                    if ($isCancelled) {
                        $color = 'grey';
                    } elseif ($stage['done']) {
                        $color = 'green';
                    } elseif ($i === $currentIndex) {
                        $color = 'yellow';
                    } else {
                        $color = 'grey';
                    }

                    // Build link for each stage
                    $link = null;
                    if ($color === 'green') {
                        // Link to the existing entity
                        if ($i === 0 && $serviceRequest->estimates->first()) {
                            $link = route('estimates.show', [$serviceRequest, $serviceRequest->estimates->first()]);
                        } elseif ($i === 1 && $serviceRequest->workOrders->first()) {
                            $link = route('work-orders.show', [$serviceRequest, $serviceRequest->workOrders->first()]);
                        } elseif ($i === 2 && $serviceRequest->invoices->first()) {
                            $link = route('invoices.show', [$serviceRequest, $serviceRequest->invoices->first()]);
                        }
                    } elseif ($color === 'yellow') {
                        // Link to create page
                        if ($i === 0) {
                            $link = route('estimates.create', $serviceRequest);
                        } elseif ($i === 1) {
                            $link = route('work-orders.create', $serviceRequest);
                        } elseif ($i === 2 && $serviceRequest->workOrders->first()) {
                            $link = route('invoices.create', [$serviceRequest, $serviceRequest->workOrders->first()]);
                        }
                    }

                    $bgClass = match ($color) {
                        'green'  => 'border border-green-500/30 bg-green-500/15 text-green-200',
                        'yellow' => 'border border-cyan-400/30 bg-cyan-500/15 text-cyan-200',
                        default  => 'border border-white/10 bg-white/5 text-gray-500',
                    };
                    $lineClass = ($i > 0 && ($stages[$i - 1]['done'] ?? false)) ? 'bg-green-400/70' : 'bg-white/10';
                @endphp

                {{-- Connector line --}}
                @if ($i > 0)
                    <div class="flex-1 h-1.5 {{ $lineClass }} rounded-full mx-1"></div>
                @endif

                {{-- Stage circle + label --}}
                <div class="flex flex-col items-center" style="min-width:5.5rem">
                    @if ($link)
                        <a href="{{ $link }}" class="w-12 h-12 rounded-full flex items-center justify-center text-base font-bold {{ $bgClass }} ring-2 ring-white/10 shadow hover:scale-110 transition-transform" title="{{ $stage['label'] }}">
                            @if ($color === 'green')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </a>
                    @else
                        <span class="w-12 h-12 rounded-full flex items-center justify-center text-base font-bold {{ $bgClass }} ring-2 ring-white/10 shadow" title="{{ $stage['label'] }}">
                            @if ($color === 'green')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                    @endif
                    <span class="mt-2 text-sm text-center leading-tight {{ $color === 'yellow' ? 'font-semibold text-cyan-300' : 'text-gray-400' }}">{{ $stage['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Customer --}}
    @if ($serviceRequest->customer)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Customer</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="block text-gray-500">Name</span>
                <span class="font-medium">{{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}</span>
            </div>
            <div>
                <span class="block text-gray-500">Phone</span>
                <span class="font-medium font-mono">{{ $serviceRequest->customer->phone }}</span>
            </div>
            <div>
                <span class="block text-gray-500">SMS Consent</span>
                @if ($serviceRequest->customer->hasSmsConsent())
                    <span class="text-green-400 font-medium">Opted in</span>
                @else
                    <span class="text-red-400 font-medium">Not opted in</span>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Vehicle & Service --}}
    @if ($serviceRequest->vehicle_make || $serviceRequest->catalogItem)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Vehicle & Service</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            @if ($serviceRequest->vehicle_make)
            <div>
                <span class="block text-gray-500">Vehicle</span>
                <span class="font-medium">
                    {{ $serviceRequest->vehicle_color }}
                    {{ $serviceRequest->vehicle_year }}
                    {{ $serviceRequest->vehicle_make }}
                    {{ $serviceRequest->vehicle_model }}
                </span>
            </div>
            @endif
            @if ($serviceRequest->catalogItem)
            <div>
                <span class="block text-gray-500">Service</span>
                <span class="font-medium">{{ $serviceRequest->catalogItem->name }}</span>
            </div>
            @endif
            @if ($serviceRequest->quoted_price)
            <div>
                <span class="block text-gray-500">Quoted Price</span>
                <span class="font-medium">${{ number_format($serviceRequest->quoted_price, 2) }}</span>
            </div>
            @endif
        </div>
        @if ($serviceRequest->notes)
        <div class="mt-4 text-sm">
            <span class="block text-gray-500">Notes</span>
            <p class="mt-1">{{ $serviceRequest->notes }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Location --}}
    <div class="surface-1 p-6">
        @php
            $assignedTechnician = $serviceRequest->assignedTechnician;
            $technicianSmsPhone = $assignedTechnician?->phone;
            $technicianHasSmsConsent = $assignedTechnician?->technicianProfile?->hasSmsConsent() ?? false;
            $hasDispatchAddress = filled($serviceRequest->location);
            $canSendTechnicianLocation = $hasDispatchAddress && $assignedTechnician && filled($technicianSmsPhone) && $technicianHasSmsConsent;
            $technicianLocationDisabledReason = ! $hasDispatchAddress
                ? 'Add an address before texting the technician.'
                : (! $assignedTechnician
                    ? 'Assign a technician to enable this SMS action.'
                    : (! filled($technicianSmsPhone)
                        ? 'Add a mobile phone number to the assigned technician account.'
                        : (! $technicianHasSmsConsent
                            ? 'The assigned technician must grant SMS consent before dispatch texts can be sent.'
                            : null)));
        @endphp
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Location</h2>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <form action="{{ route('service-requests.send-technician-location', $serviceRequest) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            @disabled(! $canSendTechnicianLocation)
                            class="text-sm font-medium px-4 py-2 rounded-md transition {{ $canSendTechnicianLocation ? 'btn-crystal-success' : 'border border-white/10 bg-white/5 text-gray-500 cursor-not-allowed' }}">
                        Send Location to Technician
                    </button>
                </form>
                @if (! $serviceRequest->location_shared_at)
                    <form action="{{ route('service-requests.request-location', $serviceRequest) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="btn-crystal text-sm font-medium px-4 py-2 rounded-md  transition">
                            Send Location Request
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($technicianLocationDisabledReason)
            <p class="mb-4 text-sm text-amber-700">{{ $technicianLocationDisabledReason }}</p>
        @elseif ($assignedTechnician)
            <p class="mb-4 text-sm text-gray-400">Technician SMS will be sent to {{ $assignedTechnician->name }} at {{ $technicianSmsPhone }}.</p>
        @endif

        @if ($serviceRequest->latitude && $serviceRequest->longitude)
            {{-- Location has been shared --}}
            <div class="bg-green-500/10 border border-green-500/30 rounded-md p-4 mb-4">
                <div class="flex items-center text-green-700 mb-2">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-semibold">Location received</span>
                    <span class="ml-2 text-sm font-normal">{{ $serviceRequest->location_shared_at?->format('M j, Y g:i A') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mb-4">
                <div>
                    <span class="block text-gray-500">Coordinates</span>
                    <span class="font-medium font-mono">{{ $serviceRequest->latitude }}, {{ $serviceRequest->longitude }}</span>
                </div>
                @if ($serviceRequest->location)
                <div>
                    <span class="block text-gray-500">Address</span>
                    <span class="font-medium">{{ $serviceRequest->location }}</span>
                </div>
                @endif
            </div>

            <div class="flex flex-wrap gap-3 mb-4">
                <a href="https://www.google.com/maps?q={{ $serviceRequest->latitude }},{{ $serviceRequest->longitude }}"
                   target="_blank"
                   class="inline-flex items-center btn-crystal text-sm font-medium px-4 py-2 rounded-md  transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Open in Google Maps
                </a>
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $serviceRequest->latitude }},{{ $serviceRequest->longitude }}"
                   target="_blank"
                   class="inline-flex items-center bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    Get Directions
                </a>
            </div>

            {{-- Embedded map --}}
            @php $mapsApiKey = \App\Models\Setting::getValue('google_maps_api_key', config('services.google_maps.api_key', '')); @endphp
            <div class="rounded-md overflow-hidden border border-white/10">
                @if($mapsApiKey)
                <iframe
                    width="100%"
                    height="300"
                    style="border:0"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps/embed/v1/place?key={{ urlencode($mapsApiKey) }}&q={{ $serviceRequest->latitude }},{{ $serviceRequest->longitude }}&zoom=15"
                    allowfullscreen>
                </iframe>
                @else
                <iframe
                    width="100%"
                    height="300"
                    style="border:0"
                    loading="lazy"
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $serviceRequest->longitude - 0.005 }},{{ $serviceRequest->latitude - 0.005 }},{{ $serviceRequest->longitude + 0.005 }},{{ $serviceRequest->latitude + 0.005 }}&layer=mapnik&marker={{ $serviceRequest->latitude }},{{ $serviceRequest->longitude }}"
                    allowfullscreen>
                </iframe>
                @endif
                </iframe>
            </div>

        @elseif ($serviceRequest->location_token && $serviceRequest->isLocationTokenValid())
            {{-- Token sent, waiting for response --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                <div class="flex items-center text-yellow-700">
                    <svg class="w-5 h-5 mr-2 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-semibold">Waiting for customer to share location&hellip;</span>
                </div>
                <p class="text-sm text-yellow-600 mt-2">
                    Link expires {{ $serviceRequest->location_token_expires_at->diffForHumans() }}.
                    <a href="{{ $serviceRequest->locationShareUrl() }}" target="_blank" class="underline font-mono">{{ $serviceRequest->locationShareUrl() }}</a>
                </p>
            </div>
            <meta http-equiv="refresh" content="10">

        @elseif ($serviceRequest->location)
            {{-- Manual location only --}}
            <div class="text-sm">
                <span class="block text-gray-500">Manual Location</span>
                <span class="font-medium">{{ $serviceRequest->location }}</span>
            </div>
        @else
            <p class="text-sm text-gray-500">No location data yet. Click "Send Location Request" to text the customer a GPS link.</p>
        @endif
    </div>

    {{-- Estimates --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Estimates</h2>
            <a href="{{ route('estimates.create', $serviceRequest) }}"
               class="btn-crystal text-sm font-medium px-4 py-2 rounded-md  transition inline-flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Create Estimate
            </a>
        </div>

        @if ($serviceRequest->estimates->isNotEmpty())
            <div class="space-y-3">
                @foreach ($serviceRequest->estimates->sortByDesc('created_at') as $estimate)
                    <a href="{{ route('estimates.show', [$serviceRequest, $estimate]) }}"
                       class="block border border-white/10 rounded-md p-4 hover:bg-white/5 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-medium text-white">Estimate #{{ $estimate->id }}</span>
                                <span class="text-xs text-gray-400 ml-2">{{ $estimate->created_at->format('M j, Y g:i A') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-bold text-white">${{ number_format($estimate->total, 2) }}</span>
                                <span @class([
                                    'px-2 py-0.5 rounded-full text-xs font-semibold',
                                    'bg-white/5 text-gray-300' => $estimate->status === 'draft',
                                    'bg-blue-100 text-blue-800' => $estimate->status === 'sent',
                                    'bg-green-100 text-green-800' => $estimate->status === 'accepted',
                                    'bg-red-100 text-red-800' => $estimate->status === 'declined',
                                ])>
                                    {{ ucfirst($estimate->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $estimate->items->count() }} item(s)
                            &middot; Subtotal ${{ number_format($estimate->subtotal, 2) }}
                            &middot; Tax ${{ number_format($estimate->tax_amount, 2) }}
                            @if($estimate->state_code)
                                ({{ $estimate->state_code }} {{ $estimate->tax_rate + 0 }}%)
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 italic">No estimates yet.</p>
        @endif
    </div>

    {{-- Work Orders --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Work Orders</h2>
            <a href="{{ route('work-orders.create', $serviceRequest) }}"
               class="bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-amber-700 transition inline-flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Create Work Order
            </a>
        </div>

        @if ($serviceRequest->workOrders->isNotEmpty())
            <div class="space-y-3">
                @foreach ($serviceRequest->workOrders->sortByDesc('created_at') as $workOrder)
                    <a href="{{ route('work-orders.show', [$serviceRequest, $workOrder]) }}"
                       class="block border border-white/10 rounded-md p-4 hover:border-amber-300 hover:bg-amber-50/40 transition">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <span class="font-semibold text-white">{{ $workOrder->work_order_number }}</span>
                                <span class="text-lg font-bold text-white">${{ number_format($workOrder->total, 2) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @php
                                    $woStatusColors = [
                                        'pending'     => 'bg-amber-100 text-amber-700',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'completed'   => 'bg-green-100 text-green-800',
                                        'cancelled'   => 'bg-white/5 text-gray-400',
                                    ];
                                    $woPriorityColors = [
                                        'low'    => 'bg-white/5 text-gray-400',
                                        'normal' => 'bg-cyan-500/10 text-cyan-400',
                                        'high'   => 'bg-amber-100 text-amber-700',
                                        'urgent' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $woPriorityColors[$workOrder->priority] ?? 'bg-white/5 text-gray-400' }}">
                                    {{ \App\Models\WorkOrder::PRIORITY_LABELS[$workOrder->priority] ?? ucfirst($workOrder->priority) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $woStatusColors[$workOrder->status] ?? 'bg-white/5 text-gray-400' }}">
                                    {{ \App\Models\WorkOrder::STATUS_LABELS[$workOrder->status] ?? ucfirst($workOrder->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $workOrder->items->count() }} item(s)
                            @if($workOrder->assigned_to)
                                &middot; Assigned: {{ $workOrder->assigned_to }}
                            @endif
                            &middot; {{ $workOrder->created_at->format('M j, Y') }}
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 italic">No work orders yet.</p>
        @endif
    </div>

    {{-- Receipts --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Receipts</h2>
        </div>

        @if ($serviceRequest->receipts->isNotEmpty())
            <div class="space-y-3">
                @foreach ($serviceRequest->receipts->sortByDesc('created_at') as $receipt)
                    <div class="border border-white/10 rounded-md p-4 flex justify-between items-center">
                        <div>
                            <span class="font-medium text-white">{{ $receipt->receipt_number }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ $receipt->created_at->format('M j, Y g:i A') }}</span>
                            <span class="text-lg font-bold text-white ml-3">${{ number_format($receipt->total, 2) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('receipts.show', [$serviceRequest, $receipt]) }}"
                               class="text-sm text-cyan-400 hover:text-cyan-300 underline">View</a>
                            <a href="{{ route('receipts.pdf', [$serviceRequest, $receipt]) }}"
                               class="inline-flex items-center text-sm text-gray-400 hover:text-white">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                PDF
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 italic">No receipts issued yet.</p>
        @endif
    </div>

    {{-- Photos --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Photos</h2>
        </div>

        @if ($serviceRequest->photos->isNotEmpty())
            @foreach (['before' => 'Before', 'during' => 'During', 'after' => 'After'] as $type => $label)
                @php $typed = $serviceRequest->photos->where('type', $type); @endphp
                @if ($typed->isNotEmpty())
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-3 mb-2">{{ $label }}</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        @foreach ($typed as $photo)
                            <div class="relative group">
                                <a href="{{ route('photos.show', [$serviceRequest, $photo]) }}" target="_blank">
                                    <img src="{{ route('photos.show', [$serviceRequest, $photo]) }}"
                                         alt="{{ $photo->caption ?: $label . ' photo' }}"
                                         class="w-full h-24 object-cover rounded-md border border-white/10">
                                </a>
                                <form method="POST" action="{{ route('photos.destroy', [$serviceRequest, $photo]) }}"
                                      class="absolute top-1 right-1 hidden group-hover:block"
                                      onsubmit="return confirm('Delete this photo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs leading-none">&times;</button>
                                </form>
                                @if ($photo->caption)
                                    <p class="text-xs text-gray-500 mt-1 truncate">{{ $photo->caption }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @else
            <p class="text-sm text-gray-400 italic">No photos yet.</p>
        @endif

        {{-- Upload form --}}
        <form method="POST" action="{{ route('photos.store', $serviceRequest) }}" enctype="multipart/form-data"
              class="mt-4 border-t border-white/10 pt-4" x-data="{ fileName: '' }">
            @csrf
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-400 mb-1">Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                           @change="fileName = $event.target.files[0]?.name || ''"
                           class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-cyan-500/10 file:text-cyan-400 hover:file:bg-cyan-500/20">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Type</label>
                    <select name="type" class="select-crystal text-sm rounded-md border-white/10 shadow-sm input-crystal">
                        <option value="before">Before</option>
                        <option value="during" selected>During</option>
                        <option value="after">After</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[120px]">
                    <label class="block text-xs font-medium text-gray-400 mb-1">Caption</label>
                    <input type="text" name="caption" maxlength="500" placeholder="Optional"
                           class="w-full text-sm rounded-md border-white/10 shadow-sm input-crystal">
                </div>
                <button type="submit" class="btn-crystal text-sm font-medium px-4 py-2 rounded-md  transition">Upload</button>
            </div>
            @error('photo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </form>
    </div>

    {{-- Signature --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Customer Signature</h2>
        </div>

        @php $capturedSig = $serviceRequest->signatures->first(fn($s) => !empty($s->signature_data)); @endphp

        @if ($capturedSig)
            <div class="border border-white/10 rounded-md p-4">
                <img src="{{ $capturedSig->signature_data }}" alt="Customer signature" class="max-h-32 mx-auto">
                <p class="text-sm text-gray-400 text-center mt-2">
                    Signed by <span class="font-medium">{{ $capturedSig->signer_name }}</span>
                    on {{ $capturedSig->signed_at->format('M j, Y g:i A') }}
                </p>
                <p class="text-xs text-gray-400 text-center">IP: {{ $capturedSig->ip_address }}</p>
            </div>
        @else
            <p class="text-sm text-gray-400 italic mb-3">No signature captured yet.</p>
            <form method="POST" action="{{ route('signatures.request', $serviceRequest) }}" class="flex items-center gap-3">
                @csrf
                <label class="flex items-center gap-2 text-sm text-gray-400">
                    <input type="checkbox" name="send_sms" value="1"
                           class="rounded border-white/10 text-cyan-400 focus:ring-cyan-500"
                           {{ $serviceRequest->customer?->hasSmsConsent() ? 'checked' : '' }}>
                    Send signing link via SMS
                </label>
                <button type="submit" class="btn-crystal text-sm font-medium px-4 py-2 rounded-md  transition">
                    Request Signature
                </button>
            </form>
        @endif
    </div>

    {{-- Payments --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Payments</h2>
            @php
                $paymentStatus = $serviceRequest->paymentStatus();
                $totalPaid = $serviceRequest->totalPayments();
            @endphp
            <span @class([
                'px-2 py-0.5 rounded-full text-xs font-semibold',
                'bg-green-100 text-green-800' => $paymentStatus === 'paid',
                'bg-yellow-100 text-yellow-800' => $paymentStatus === 'partial',
                'bg-white/5 text-gray-400' => $paymentStatus === 'unpaid',
            ])>
                {{ ucfirst($paymentStatus) }}
                @if ($totalPaid > 0)
                    &middot; ${{ number_format($totalPaid, 2) }}
                @endif
            </span>
        </div>

        @if ($serviceRequest->paymentRecords->isNotEmpty())
            <div class="space-y-2">
                @foreach ($serviceRequest->paymentRecords->sortByDesc('collected_at') as $payment)
                    <div class="flex justify-between items-center border border-white/10 rounded-md p-3 text-sm">
                        <div>
                            <span class="font-medium">${{ number_format($payment->amount, 2) }}</span>
                            <span class="text-gray-500 ml-2">{{ $payment->methodLabel() }}</span>
                            @if ($payment->reference)
                                <span class="text-gray-400 ml-1">#{{ $payment->reference }}</span>
                            @endif
                            <span class="text-xs text-gray-400 ml-2">{{ $payment->collected_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <form method="POST" action="{{ route('payments.destroy', [$serviceRequest, $payment]) }}"
                              onsubmit="return confirm('Delete this payment record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-400 text-xs">Delete</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Record payment form --}}
        <form method="POST" action="{{ route('payments.store', $serviceRequest) }}"
              class="mt-4 border-t border-white/10 pt-4">
            @csrf
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Method <span class="text-red-500">*</span></label>
                    <select name="method" required class="select-crystal w-full text-sm rounded-md border-white/10 shadow-sm input-crystal">
                        @foreach (\App\Models\PaymentRecord::METHOD_LABELS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="w-full text-sm rounded-md border-white/10 shadow-sm input-crystal">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Reference</label>
                    <input type="text" name="reference" maxlength="200" placeholder="Transaction ID"
                           class="w-full text-sm rounded-md border-white/10 shadow-sm input-crystal">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-700 transition">Record Payment</button>
                </div>
            </div>
            @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            @error('method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </form>
    </div>

    {{-- Service Log --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-gray-300">Activity Log</h2>
            <a href="{{ route('service-requests.evidence', $serviceRequest) }}"
               class="text-sm text-cyan-400 hover:text-cyan-300 underline">View Evidence Package</a>
        </div>

        @if ($serviceRequest->serviceLogs->isNotEmpty())
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach ($serviceRequest->serviceLogs as $log)
                    <div class="flex items-start gap-3 text-sm border-b border-gray-50 pb-2">
                        <div class="flex-shrink-0 mt-1">
                            <div @class([
                                'w-2 h-2 rounded-full',
                                'bg-blue-400' => $log->event === 'status_change',
                                'bg-green-400' => in_array($log->event, ['payment_collected', 'signature_captured']),
                                'bg-yellow-400' => $log->event === 'photo_uploaded',
                                'bg-gray-400' => !in_array($log->event, ['status_change', 'payment_collected', 'signature_captured', 'photo_uploaded']),
                            ])></div>
                        </div>
                        <div class="flex-1">
                            <span class="font-medium text-gray-300">{{ $log->eventLabel() }}</span>
                            @if ($log->user)
                                <span class="text-gray-400">by {{ $log->user->name }}</span>
                            @endif
                            <span class="text-xs text-gray-400 ml-1">{{ $log->logged_at->format('M j g:i A') }}</span>
                            @if ($log->details)
                                @if (!empty($log->details['notes']) || !empty($log->details['note']))
                                    <p class="text-xs text-gray-500 mt-0.5 italic">{{ $log->details['notes'] ?? $log->details['note'] }}</p>
                                @endif
                                @if (!empty($log->details['old_status']) && !empty($log->details['new_status']))
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ \App\Models\ServiceRequest::STATUS_LABELS[$log->details['old_status']] ?? $log->details['old_status'] }}
                                        &rarr;
                                        {{ \App\Models\ServiceRequest::STATUS_LABELS[$log->details['new_status']] ?? $log->details['new_status'] }}
                                    </p>
                                @endif
                                @if (!empty($log->details['amount']))
                                    <p class="text-xs text-gray-500 mt-0.5">${{ number_format($log->details['amount'], 2) }}
                                        @if (!empty($log->details['method'])) via {{ $log->details['method'] }} @endif
                                    </p>
                                @endif
                                @if (!empty($log->details['technician_name']) || !empty($log->details['address']))
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        @if (!empty($log->details['technician_name']))
                                            {{ $log->details['technician_name'] }}
                                        @endif
                                        @if (!empty($log->details['recipient_phone']))
                                            &middot; {{ $log->details['recipient_phone'] }}
                                        @endif
                                        @if (!empty($log->details['address']))
                                            &middot; {{ $log->details['address'] }}
                                        @endif
                                    </p>
                                @endif
                                @if (!empty($log->details['sms_error']))
                                    <p class="text-xs text-red-400 mt-0.5">{{ $log->details['sms_error'] }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 italic">No activity logged yet.</p>
        @endif
    </div>

    {{-- Correspondence Log --}}
    <div x-data="{ showCorrespondenceForm: false }">
        @include('partials.correspondence-timeline')
    </div>

    {{-- Messages --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Messages</h2>

        {{-- Conversation thread (chronological) --}}
        <div class="space-y-3 mb-6 max-h-96 overflow-y-auto" id="message-thread">
            @forelse ($serviceRequest->messages->sortBy('created_at') as $message)
            <div @class([
                'flex',
                'justify-end' => $message->direction === 'outbound',
                'justify-start' => $message->direction === 'inbound',
            ])>
                <div @class([
                    'max-w-xs sm:max-w-sm md:max-w-md rounded-lg px-4 py-2 text-sm',
                    'bg-blue-600 text-white'            => $message->direction === 'outbound',
                    'bg-white/5 text-white border border-white/10' => $message->direction === 'inbound',
                ])>
                    <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                    <p @class([
                        'text-xs mt-1',
                        'text-blue-200' => $message->direction === 'outbound',
                        'text-gray-400' => $message->direction === 'inbound',
                    ])>
                        {{ $message->created_at->format('M j g:i A') }}
                        @if ($message->direction === 'outbound' && $message->status === 'failed')
                            <span class="text-red-300 font-semibold ml-1">Failed</span>
                        @endif
                    </p>
                </div>
            </div>
            @empty
                <p class="text-sm text-gray-400 italic">No messages yet.</p>
            @endforelse
        </div>

        {{-- Compose form --}}
        @if ($serviceRequest->customer)
        <div class="border-t border-white/10 pt-4" x-data="messageCompose({{ $serviceRequest->id }})">
            @if (! $serviceRequest->customer->hasSmsConsent())
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 text-sm text-yellow-700">
                    Customer has not opted in to SMS. Send a location request or have them text <strong>START</strong> to opt in.
                </div>
            @else
                <form action="{{ route('service-requests.messages.store', $serviceRequest) }}" method="POST">
                    @csrf

                    {{-- Template picker --}}
                    <div class="mb-3">
                        <select x-model="selectedTemplateId"
                                @change="loadTemplate()"
                                class="w-full text-sm border-white/10 rounded-md shadow-sm input-crystal">
                            <option value="">Write a custom message&hellip;</option>
                            @foreach ($messageTemplates->groupBy('category') as $category => $templates)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $category)) }}">
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="template_id" :value="selectedTemplateId || ''">

                    <div class="flex gap-2">
                        <textarea name="body"
                                  x-ref="body"
                                  x-model="body"
                                  rows="2"
                                  maxlength="1600"
                                  placeholder="Type a message&hellip;"
                                  class="flex-1 text-sm border-white/10 rounded-md shadow-sm input-crystal resize-none"
                                  required></textarea>
                        <button type="submit"
                                class="self-end btn-crystal text-sm font-medium px-4 py-2 rounded-md  transition whitespace-nowrap">
                            Send
                        </button>
                    </div>

                    <p class="text-xs text-gray-400 mt-1" x-show="body.length > 0" x-cloak>
                        <span x-text="body.length"></span>/1600
                    </p>
                </form>
            @endif
        </div>
        @endif
    </div>

    @if ($serviceRequest->customer?->hasSmsConsent())
    @push('scripts')
    <script>
        function messageCompose(serviceRequestId) {
            return {
                selectedTemplateId: '',
                body: '',
                loading: false,
                loadTemplate() {
                    if (!this.selectedTemplateId) {
                        this.body = '';
                        return;
                    }
                    this.loading = true;
                    fetch('{{ route("api.message-templates.render") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            template_id: this.selectedTemplateId,
                            service_request_id: serviceRequestId,
                        }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.body = data.rendered || '';
                        this.loading = false;
                    })
                    .catch(() => { this.loading = false; });
                },
            };
        }

        // Auto-scroll to bottom of message thread
        document.addEventListener('DOMContentLoaded', function () {
            const thread = document.getElementById('message-thread');
            if (thread) thread.scrollTop = thread.scrollHeight;
        });
    </script>
    @endpush
    @endif

    {{-- Status History --}}
    @if ($serviceRequest->statusLogs->isNotEmpty())
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Status History</h2>
        <div class="space-y-3">
            @foreach ($serviceRequest->statusLogs->sortByDesc('created_at') as $log)
            <div class="flex items-start gap-3 text-sm">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$log->old_status" />
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <x-status-badge :status="$log->new_status" />
                    </div>
                    <p class="text-gray-500 text-xs mt-1">
                        {{ $log->created_at->format('M j, Y g:i A') }}
                        @if ($log->user)
                            &middot; by {{ $log->user->name }}
                        @endif
                    </p>
                    @if ($log->notes)
                        <p class="text-gray-400 text-xs mt-0.5 italic">{{ $log->notes }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Documents --}}
    @include('partials.document-list', [
        'documents' => $serviceRequest->documents,
        'uploadUrl' => route('documents.store-generic', ['type' => 'service-request', 'id' => $serviceRequest->id]),
    ])

    {{-- Back link --}}
    <div class="flex gap-3">
        <a href="{{ route('service-requests.index') }}"
           class="text-sm text-gray-500 hover:text-cyan-400 underline">&larr; All Tickets</a>
        <a href="{{ route('service-requests.create') }}"
           class="text-sm text-cyan-400 hover:text-cyan-300 underline">+ New Ticket</a>
    </div>
</div>
@endsection
