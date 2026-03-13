<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TechnicianProfileController extends Controller
{
    public function index()
    {
        abort_unless(Setting::getValue('compliance_tracking_enabled'), 404);

        $users = User::with('technicianProfile')->orderBy('name')->get();

        return view('technician-profiles.index', compact('users'));
    }

    public function show(User $user)
    {
        abort_unless(Setting::getValue('compliance_tracking_enabled'), 404);

        $user->load('technicianProfile');

        return view('technician-profiles.show', compact('user'));
    }

    public function edit(User $user)
    {
        abort_unless(Setting::getValue('compliance_tracking_enabled'), 404);

        $profile = $user->technicianProfile ?? new TechnicianProfile();

        return view('technician-profiles.edit', compact('user', 'profile'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(Setting::getValue('compliance_tracking_enabled'), 404);

        $isSelfService = (int) $request->user()?->id === (int) $user->id;

        $validated = $request->validate([
            'drivers_license_number'  => ['nullable', 'string', 'max:50'],
            'drivers_license_expiry'  => ['nullable', 'date'],
            'insurance_policy_number' => ['nullable', 'string', 'max:100'],
            'insurance_expiry'        => ['nullable', 'date'],
            'background_check_date'   => ['nullable', 'date'],
            'background_check_status' => ['nullable', 'string', 'in:clear,pending,failed'],
            'drug_screen_date'        => ['nullable', 'date'],
            'drug_screen_status'      => ['nullable', 'string', 'in:clear,pending,failed'],
            'emergency_contact_name'  => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'user_phone'              => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $normalized = User::normalizePhone(is_string($value) ? $value : null);

                    if ($normalized !== null && strlen($normalized) < 10) {
                        $fail('The mobile phone must contain at least 10 digits.');
                    }
                },
            ],
            'grant_sms_consent'       => ['nullable', 'boolean'],
            'vehicle_year'            => ['nullable', 'string', 'max:4'],
            'vehicle_make'            => ['nullable', 'string', 'max:50'],
            'vehicle_model'           => ['nullable', 'string', 'max:50'],
            'vehicle_plate'           => ['nullable', 'string', 'max:20'],
            'certifications'          => ['nullable', 'array'],
            'certifications.*.name'        => ['nullable', 'string', 'max:100'],
            'certifications.*.issued_date' => ['nullable', 'date'],
            'certifications.*.expiry_date' => ['nullable', 'date'],
        ]);

        if ($isSelfService && array_key_exists('user_phone', $validated)) {
            $user->forceFill([
                'phone' => $validated['user_phone'],
            ])->save();
        }

        if ($request->boolean('grant_sms_consent') && ! $isSelfService) {
            return back()->withErrors([
                'grant_sms_consent' => 'Technicians must grant their own SMS consent while signed in to their own account.',
            ])->withInput();
        }

        if ($request->boolean('grant_sms_consent') && ! filled($user->phone)) {
            return back()->withErrors([
                'user_phone' => 'Add your mobile phone number before granting SMS consent.',
            ])->withInput();
        }

        // Filter out empty certification rows
        if (isset($validated['certifications'])) {
            $validated['certifications'] = array_values(
                array_filter($validated['certifications'], fn ($c) => !empty($c['name']))
            );
        }

        $profile = $user->technicianProfile()->updateOrCreate(
            ['user_id' => $user->id],
            Arr::except($validated, ['user_phone', 'grant_sms_consent']),
        );

        if ($isSelfService && $request->boolean('grant_sms_consent') && ! $profile->hasSmsConsent()) {
            $profile->grantSmsConsent([
                'source' => 'technician_profile_self_service',
                'recorded_by_user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        return redirect()
            ->route('technician-profiles.show', $user)
            ->with('success', 'Compliance profile updated.');
    }
}
