<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'vehicle_year'            => ['nullable', 'string', 'max:4'],
            'vehicle_make'            => ['nullable', 'string', 'max:50'],
            'vehicle_model'           => ['nullable', 'string', 'max:50'],
            'vehicle_plate'           => ['nullable', 'string', 'max:20'],
            'certifications'          => ['nullable', 'array'],
            'certifications.*.name'        => ['nullable', 'string', 'max:100'],
            'certifications.*.issued_date' => ['nullable', 'date'],
            'certifications.*.expiry_date' => ['nullable', 'date'],
        ]);

        // Filter out empty certification rows
        if (isset($validated['certifications'])) {
            $validated['certifications'] = array_values(
                array_filter($validated['certifications'], fn ($c) => !empty($c['name']))
            );
        }

        $user->technicianProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated,
        );

        return redirect()
            ->route('technician-profiles.show', $user)
            ->with('success', 'Compliance profile updated.');
    }
}
