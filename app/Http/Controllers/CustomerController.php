<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

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

        if (!$phone) {
            return response()->json(['customer' => null]);
        }

        // Normalize to digits for consistent lookup
        $phone = preg_replace('/\D/', '', $phone);

        $customer = Customer::where('phone', $phone)
            ->where('is_active', true)
            ->first();

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
}
