{{-- Technician Profile Show — technician-profiles.show | Feature preservation notes: Header with user->name and Edit Profile link (technician-profiles.edit) + Back link (technician-profiles.index); Session success flash; Empty-state when no technicianProfile with Create Profile link; License & Insurance dl block (drivers_license_number/expiry + x-compliance-badge licenseStatus(), insurance_policy_number/expiry + insuranceStatus()); Background & Drug Screen dl block (dates + x-compliance-badge for background_check_status and drug_screen_status); Certifications table (name, issued_date, expiry_date + x-compliance-badge via TechnicianProfile::dateStatus()); Emergency Contact & Vehicle dl block (emergency_contact_name/phone, user->phone, hasSmsConsent() + sms_consent_at, vehicle_year/make/model/plate). Layout: widened to max-w-7xl for consistent internal-tool layout; All forms, routes, and PHP logic kept intact. --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ $user->name }} — Compliance</h1>
        <div class="flex gap-3">
            <a href="{{ route('technician-profiles.edit', $user) }}"
               class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-medium rounded-lg ">
                Edit Profile
            </a>
            <a href="{{ route('technician-profiles.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white/10 text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300">
                Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-500/10 p-4">
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    @php $p = $user->technicianProfile; @endphp

    @if(!$p)
        <div class="surface-1 p-8 text-center">
            <p class="text-gray-500">No compliance profile created yet.</p>
            <a href="{{ route('technician-profiles.edit', $user) }}"
               class="mt-4 inline-flex items-center px-4 py-2 btn-crystal text-sm font-medium rounded-lg ">
                Create Profile
            </a>
        </div>
    @else
        {{-- License & Insurance --}}
        <div class="surface-1 p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">License & Insurance</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="font-medium text-gray-500">Driver's License #</dt>
                    <dd class="mt-1 text-white">{{ $p->drivers_license_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">License Expiry</dt>
                    <dd class="mt-1 flex items-center gap-2">
                        {{ $p->drivers_license_expiry?->format('M j, Y') ?? '—' }}
                        @if($p->drivers_license_expiry)
                            <x-compliance-badge :status="$p->licenseStatus()" />
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Insurance Policy #</dt>
                    <dd class="mt-1 text-white">{{ $p->insurance_policy_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Insurance Expiry</dt>
                    <dd class="mt-1 flex items-center gap-2">
                        {{ $p->insurance_expiry?->format('M j, Y') ?? '—' }}
                        @if($p->insurance_expiry)
                            <x-compliance-badge :status="$p->insuranceStatus()" />
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Background & Drug Screen --}}
        <div class="surface-1 p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">Background & Drug Screen</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="font-medium text-gray-500">Background Check Date</dt>
                    <dd class="mt-1 text-white">{{ $p->background_check_date?->format('M j, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Background Check Status</dt>
                    <dd class="mt-1"><x-compliance-badge :status="$p->background_check_status" /></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Drug Screen Date</dt>
                    <dd class="mt-1 text-white">{{ $p->drug_screen_date?->format('M j, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Drug Screen Status</dt>
                    <dd class="mt-1"><x-compliance-badge :status="$p->drug_screen_status" /></dd>
                </div>
            </dl>
        </div>

        {{-- Certifications --}}
        @if(!empty($p->certifications))
            <div class="surface-1 p-6 mb-6">
                <h2 class="text-lg font-semibold text-white mb-4">Certifications</h2>
                <table class="table-crystal min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-2 pr-4 font-medium">Name</th>
                            <th class="pb-2 pr-4 font-medium">Issued</th>
                            <th class="pb-2 font-medium">Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($p->certifications as $cert)
                            <tr class="border-b last:border-0">
                                <td class="py-2 pr-4 text-white">{{ $cert['name'] ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-400">{{ isset($cert['issued_date']) ? \Carbon\Carbon::parse($cert['issued_date'])->format('M j, Y') : '—' }}</td>
                                <td class="py-2 flex items-center gap-2">
                                    {{ isset($cert['expiry_date']) ? \Carbon\Carbon::parse($cert['expiry_date'])->format('M j, Y') : '—' }}
                                    @if(isset($cert['expiry_date']))
                                        <x-compliance-badge :status="\App\Models\TechnicianProfile::dateStatus(\Carbon\Carbon::parse($cert['expiry_date']))" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Emergency Contact & Vehicle --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Emergency Contact & Vehicle</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="font-medium text-gray-500">Emergency Contact</dt>
                    <dd class="mt-1 text-white">{{ $p->emergency_contact_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Emergency Phone</dt>
                    <dd class="mt-1 text-white">{{ $p->emergency_contact_phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Mobile Phone</dt>
                    <dd class="mt-1 text-white">{{ $user->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">SMS Consent</dt>
                    <dd class="mt-1 text-white">{{ $p->hasSmsConsent() ? 'Granted on ' . $p->sms_consent_at?->format('M j, Y g:i A') : 'Not granted' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="font-medium text-gray-500">Service Vehicle</dt>
                    <dd class="mt-1 text-white">
                        @if($p->vehicle_year || $p->vehicle_make || $p->vehicle_model)
                            {{ trim("{$p->vehicle_year} {$p->vehicle_make} {$p->vehicle_model}") }}
                            @if($p->vehicle_plate) — {{ $p->vehicle_plate }} @endif
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    @endif
</div>
@endsection
