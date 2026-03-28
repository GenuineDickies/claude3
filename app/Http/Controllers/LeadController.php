<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $stage = trim((string) $request->query('stage', ''));

        $leads = Lead::query()
            ->with('assignedUser')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('service_needed', 'like', "%{$search}%");
                });
            })
            ->when(in_array($stage, Lead::STAGES, true), fn ($query) => $query->where('stage', $stage))
            ->latest()
            ->paginate(20);

        return view('leads.index', [
            'leads' => $leads,
            'currentSearch' => $search,
            'currentStage' => $stage,
            'stageOptions' => Lead::stageOptions(),
        ]);
    }

    public function create(): View
    {
        return view('leads.create', [
            'stageOptions' => Lead::stageOptions(),
            'users' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'stage' => ['required', 'string', 'in:' . implode(',', Lead::STAGES)],
            'source' => ['required', 'string', 'max:64'],
            'service_needed' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['phone'] = Customer::normalizePhone($validated['phone']);

        $lead = Lead::query()->create($validated);

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Inbound request created.');
    }

    public function show(Lead $lead): View
    {
        $lead->load(['assignedUser', 'convertedCustomer', 'convertedServiceRequest']);

        return view('leads.show', [
            'lead' => $lead,
            'stageOptions' => Lead::stageOptions(),
            'users' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'stage' => ['required', 'string', 'in:' . implode(',', Lead::STAGES)],
            'source' => ['required', 'string', 'max:64'],
            'service_needed' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['phone'] = Customer::normalizePhone($validated['phone']);
        $lead->update($validated);

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Inbound request updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index')
            ->with('success', 'Inbound request deleted.');
    }

    public function startIntake(Lead $lead): RedirectResponse
    {
        $query = [
            'lead_id' => $lead->id,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'phone' => $lead->phone,
            'street_address' => $lead->location,
            'notes' => $lead->notes,
        ];

        return redirect()->route('service-requests.create', $query);
    }
}
