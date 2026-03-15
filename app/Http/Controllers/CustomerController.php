<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('serviceRequests')->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->query('consent') === 'yes') {
            $query->whereNotNull('sms_consent_at')
                  ->where(function ($q) {
                      $q->whereNull('sms_opt_out_at')
                        ->orWhereColumn('sms_consent_at', '>', 'sms_opt_out_at');
                  });
        } elseif ($request->query('consent') === 'no') {
            $query->where(function ($q) {
                $q->whereNull('sms_consent_at')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('sms_opt_out_at')
                         ->whereColumn('sms_opt_out_at', '>=', 'sms_consent_at');
                  });
            });
        }

        if ($request->query('active') === '1') {
            $query->where('is_active', true);
        } elseif ($request->query('active') === '0') {
            $query->where('is_active', false);
        }

        $customers = $query->paginate(20);

        return view('customers.index', [
            'customers' => $customers,
            'currentSearch' => $search,
            'currentConsent' => $request->query('consent'),
            'currentActive' => $request->query('active'),
        ]);
    }

    public function search(Request $request)
    {
        $phone = $request->query('phone');

        if (! $phone) {
            return response()->json(['customer' => null]);
        }

        $customer = Customer::findActiveByPhone($phone);

        $vehicle = null;
        if ($customer) {
            // Get the most recent vehicle for this customer
            $vehicle = $customer->vehicles()->latest()->first();
        }

        return response()->json([
            'customer' => $customer,
            'vehicle' => $vehicle,
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'vehicles',
            'serviceRequests' => fn ($query) => $query
                ->with(['catalogItem', 'invoices'])
                ->latest()
                ->limit(10),
            'messages' => fn ($query) => $query->latest()->limit(10),
            'correspondences' => fn ($query) => $query->with('logger')->latest('logged_at')->limit(10),
        ]);

        return view('customers.show', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.status_updates' => ['nullable', 'boolean'],
            'notification_preferences.location_requests' => ['nullable', 'boolean'],
            'notification_preferences.signature_requests' => ['nullable', 'boolean'],
            'notification_preferences.marketing' => ['nullable', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive) {
            Customer::query()
                ->whereKeyNot($customer->id)
                ->wherePhoneMatches($validated['phone'])
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $customer->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'is_active' => $isActive,
            'notification_preferences' => [
                'status_updates' => $request->boolean('notification_preferences.status_updates'),
                'location_requests' => $request->boolean('notification_preferences.location_requests'),
                'signature_requests' => $request->boolean('notification_preferences.signature_requests'),
                'marketing' => $request->boolean('notification_preferences.marketing'),
            ],
        ]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer details updated.');
    }
}
