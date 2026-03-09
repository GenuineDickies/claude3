<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        if ($request->input('active') !== null && $request->input('active') !== '') {
            $query->where('is_active', $request->boolean('active'));
        }

        $vendors = $query->paginate(25)->withQueryString();

        return view('vendors.index', [
            'vendors'       => $vendors,
            'currentSearch' => $search,
            'currentActive' => $request->input('active'),
        ]);
    }

    public function create()
    {
        $expenseAccounts = Account::general()
            ->where('is_active', true)
            ->whereIn('type', ['expense', 'cogs'])
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('vendors.create', compact('expenseAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                      => 'required|string|max:200',
            'contact_name'              => 'nullable|string|max:200',
            'email'                     => 'nullable|email|max:200',
            'phone'                     => 'nullable|string|max:30',
            'address'                   => 'nullable|string|max:300',
            'city'                      => 'nullable|string|max:100',
            'state'                     => 'nullable|string|max:2',
            'zip'                       => 'nullable|string|max:10',
            'account_number'            => 'nullable|string|max:100',
            'payment_terms'             => 'nullable|string|max:100',
            'default_expense_account_id' => 'nullable|exists:accounts,id',
            'notes'                     => 'nullable|string|max:5000',
        ]);

        $validated['is_active'] = true;

        $vendor = Vendor::create($validated);

        return redirect()->route('vendors.show', $vendor)
            ->with('success', 'Vendor "' . $vendor->name . '" created.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['defaultExpenseAccount', 'documents' => function ($q) {
            $q->latest('document_date')->take(10);
        }]);

        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        $expenseAccounts = Account::general()
            ->where('is_active', true)
            ->whereIn('type', ['expense', 'cogs'])
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('vendors.edit', compact('vendor', 'expenseAccounts'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name'                      => 'required|string|max:200',
            'contact_name'              => 'nullable|string|max:200',
            'email'                     => 'nullable|email|max:200',
            'phone'                     => 'nullable|string|max:30',
            'address'                   => 'nullable|string|max:300',
            'city'                      => 'nullable|string|max:100',
            'state'                     => 'nullable|string|max:2',
            'zip'                       => 'nullable|string|max:10',
            'account_number'            => 'nullable|string|max:100',
            'payment_terms'             => 'nullable|string|max:100',
            'default_expense_account_id' => 'nullable|exists:accounts,id',
            'is_active'                 => 'boolean',
            'notes'                     => 'nullable|string|max:5000',
        ]);

        $vendor->update($validated);

        return redirect()->route('vendors.show', $vendor)
            ->with('success', 'Vendor updated.');
    }
}
