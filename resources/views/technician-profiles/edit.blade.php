@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ $user->name }} — Edit Compliance</h1>
        <a href="{{ route('technician-profiles.index') }}"
           class="inline-flex items-center px-4 py-2 bg-white/10 text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-300">
            Back
        </a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('technician-profiles.update', $user) }}">
        @csrf
        @method('PUT')

        {{-- License & Insurance --}}
        <div class="surface-1 p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">License & Insurance</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="drivers_license_number" class="block text-sm font-medium text-gray-300 mb-1">Driver's License #</label>
                    <input type="text" name="drivers_license_number" id="drivers_license_number"
                           value="{{ old('drivers_license_number', $profile->drivers_license_number) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="drivers_license_expiry" class="block text-sm font-medium text-gray-300 mb-1">License Expiry</label>
                    <input type="date" name="drivers_license_expiry" id="drivers_license_expiry"
                           value="{{ old('drivers_license_expiry', $profile->drivers_license_expiry?->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="insurance_policy_number" class="block text-sm font-medium text-gray-300 mb-1">Insurance Policy #</label>
                    <input type="text" name="insurance_policy_number" id="insurance_policy_number"
                           value="{{ old('insurance_policy_number', $profile->insurance_policy_number) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="insurance_expiry" class="block text-sm font-medium text-gray-300 mb-1">Insurance Expiry</label>
                    <input type="date" name="insurance_expiry" id="insurance_expiry"
                           value="{{ old('insurance_expiry', $profile->insurance_expiry?->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
            </div>
        </div>

        {{-- Background & Drug Screen --}}
        <div class="surface-1 p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">Background & Drug Screen</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="background_check_date" class="block text-sm font-medium text-gray-300 mb-1">Background Check Date</label>
                    <input type="date" name="background_check_date" id="background_check_date"
                           value="{{ old('background_check_date', $profile->background_check_date?->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="background_check_status" class="block text-sm font-medium text-gray-300 mb-1">Background Status</label>
                    <select name="background_check_status" id="background_check_status"
                            class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                        <option value="">— Not set —</option>
                        <option value="clear" @selected(old('background_check_status', $profile->background_check_status) === 'clear')>Clear</option>
                        <option value="pending" @selected(old('background_check_status', $profile->background_check_status) === 'pending')>Pending</option>
                        <option value="failed" @selected(old('background_check_status', $profile->background_check_status) === 'failed')>Failed</option>
                    </select>
                </div>
                <div>
                    <label for="drug_screen_date" class="block text-sm font-medium text-gray-300 mb-1">Drug Screen Date</label>
                    <input type="date" name="drug_screen_date" id="drug_screen_date"
                           value="{{ old('drug_screen_date', $profile->drug_screen_date?->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="drug_screen_status" class="block text-sm font-medium text-gray-300 mb-1">Drug Screen Status</label>
                    <select name="drug_screen_status" id="drug_screen_status"
                            class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                        <option value="">— Not set —</option>
                        <option value="clear" @selected(old('drug_screen_status', $profile->drug_screen_status) === 'clear')>Clear</option>
                        <option value="pending" @selected(old('drug_screen_status', $profile->drug_screen_status) === 'pending')>Pending</option>
                        <option value="failed" @selected(old('drug_screen_status', $profile->drug_screen_status) === 'failed')>Failed</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Certifications --}}
        <div class="surface-1 p-6 mb-6" x-data="certifications()">
            <h2 class="text-lg font-semibold text-white mb-4">Certifications</h2>
            <template x-for="(cert, idx) in certs" :key="idx">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-3 items-end">
                    <div class="sm:col-span-2">
                        <label x-show="idx === 0" class="block text-sm font-medium text-gray-300 mb-1">Name</label>
                        <input type="text" :name="'certifications[' + idx + '][name]'" x-model="cert.name"
                               class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal"
                               placeholder="e.g. CPR / First Aid">
                    </div>
                    <div>
                        <label x-show="idx === 0" class="block text-sm font-medium text-gray-300 mb-1">Issued</label>
                        <input type="date" :name="'certifications[' + idx + '][issued_date]'" x-model="cert.issued_date"
                               class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label x-show="idx === 0" class="block text-sm font-medium text-gray-300 mb-1">Expiry</label>
                            <input type="date" :name="'certifications[' + idx + '][expiry_date]'" x-model="cert.expiry_date"
                                   class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                        </div>
                        <button type="button" @click="certs.splice(idx, 1)" class="text-red-500 hover:text-red-700 self-end pb-2" title="Remove">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </template>
            <button type="button" @click="certs.push({name:'', issued_date:'', expiry_date:''})"
                    class="text-sm text-cyan-400 hover:text-cyan-300 font-medium">
                + Add Certification
            </button>
        </div>

        {{-- Emergency Contact & Vehicle --}}
        <div class="surface-1 p-6 mb-6">
            <h2 class="text-lg font-semibold text-white mb-4">Emergency Contact, SMS & Vehicle</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="emergency_contact_name" class="block text-sm font-medium text-gray-300 mb-1">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                           value="{{ old('emergency_contact_name', $profile->emergency_contact_name) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-300 mb-1">Emergency Phone</label>
                    <input type="text" name="emergency_contact_phone" id="emergency_contact_phone"
                           value="{{ old('emergency_contact_phone', $profile->emergency_contact_phone) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="user_phone" class="block text-sm font-medium text-gray-300 mb-1">Mobile Phone</label>
                    <input type="text" name="user_phone" id="user_phone"
                           value="{{ old('user_phone', $user->phone) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal"
                           placeholder="5551234567"
                           @disabled(auth()->id() !== $user->id)>
                    <p class="mt-1 text-xs text-gray-500">
                        @if (auth()->id() === $user->id)
                            This number is reused for technician dispatch SMS so you only have to enter it once.
                        @else
                            Only the technician can add or change their own mobile number and SMS consent.
                        @endif
                    </p>
                    @error('user_phone')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2 rounded-lg border border-white/10 bg-white/5 p-4">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="grant_sms_consent" id="grant_sms_consent" value="1"
                               class="mt-1 rounded border-white/10 text-cyan-400 focus:ring-cyan-500"
                               @checked(old('grant_sms_consent'))
                               @disabled(auth()->id() !== $user->id || $profile->hasSmsConsent())>
                        <div>
                            <label for="grant_sms_consent" class="block text-sm font-medium text-gray-200">Authorize dispatch SMS to this mobile number</label>
                            <p class="mt-1 text-xs text-gray-400">
                                @if ($profile->hasSmsConsent())
                                    Consent already recorded {{ $profile->sms_consent_at?->format('M j, Y g:i A') }}.
                                @elseif (auth()->id() === $user->id)
                                    By checking this box, you authorize dispatch and service-location texts to your mobile number. Reply STOP to opt out and HELP for help.
                                @else
                                    Consent must be granted by the technician while signed in to their own account.
                                @endif
                            </p>
                            @error('grant_sms_consent')
                                <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div>
                    <label for="vehicle_year" class="block text-sm font-medium text-gray-300 mb-1">Vehicle Year</label>
                    <input type="text" name="vehicle_year" id="vehicle_year" maxlength="4"
                           value="{{ old('vehicle_year', $profile->vehicle_year) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="vehicle_make" class="block text-sm font-medium text-gray-300 mb-1">Vehicle Make</label>
                    <input type="text" name="vehicle_make" id="vehicle_make"
                           value="{{ old('vehicle_make', $profile->vehicle_make) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="vehicle_model" class="block text-sm font-medium text-gray-300 mb-1">Vehicle Model</label>
                    <input type="text" name="vehicle_model" id="vehicle_model"
                           value="{{ old('vehicle_model', $profile->vehicle_model) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
                <div>
                    <label for="vehicle_plate" class="block text-sm font-medium text-gray-300 mb-1">License Plate</label>
                    <input type="text" name="vehicle_plate" id="vehicle_plate"
                           value="{{ old('vehicle_plate', $profile->vehicle_plate) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg  transition-colors">
                Save Profile
            </button>
        </div>
    </form>
</div>

@php
    $initialCerts = old('certifications', $profile->certifications ?? []) ?: [['name' => '', 'issued_date' => '', 'expiry_date' => '']];
@endphp
<script>
function certifications() {
    return {
        certs: @json($initialCerts)
    };
}
</script>
@endsection
