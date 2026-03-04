<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('creator')->latest('date');

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($vendor = $request->input('vendor')) {
            $query->where('vendor', 'like', '%' . $vendor . '%');
        }

        if ($from = $request->input('from')) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('date', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('vendor', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('expense_number', 'like', "%{$search}%");
            });
        }

        $expenses = $query->paginate(25)->withQueryString();

        return view('expenses.index', [
            'expenses'        => $expenses,
            'categories'      => Expense::CATEGORIES,
            'currentCategory' => $category,
            'currentSearch'   => $search,
            'currentFrom'     => $from,
            'currentTo'       => $to,
        ]);
    }

    public function create()
    {
        return view('expenses.create', [
            'categories'     => Expense::CATEGORIES,
            'paymentMethods' => Expense::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'             => 'required|date',
            'vendor'           => 'required|string|max:200',
            'description'      => 'nullable|string|max:500',
            'category'         => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => ['nullable', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'reference_number' => 'nullable|string|max:100',
            'receipt'          => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf|max:10240',
            'notes'            => 'nullable|string|max:5000',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('expenses', 'local');
        }

        $expense = Expense::create([
            'expense_number'   => Expense::generateExpenseNumber(),
            'date'             => $validated['date'],
            'vendor'           => $validated['vendor'],
            'description'      => $validated['description'] ?? null,
            'category'         => $validated['category'],
            'amount'           => $validated['amount'],
            'payment_method'   => $validated['payment_method'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'receipt_path'     => $receiptPath,
            'notes'            => $validated['notes'] ?? null,
            'created_by'       => Auth::id(),
        ]);

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Expense ' . $expense->expense_number . ' recorded.');
    }

    public function show(Expense $expense)
    {
        $expense->load('creator', 'documents.uploader');

        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', [
            'expense'        => $expense,
            'categories'     => Expense::CATEGORIES,
            'paymentMethods' => Expense::PAYMENT_METHODS,
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'date'             => 'required|date',
            'vendor'           => 'required|string|max:200',
            'description'      => 'nullable|string|max:500',
            'category'         => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => ['nullable', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'reference_number' => 'nullable|string|max:100',
            'receipt'          => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf|max:10240',
            'notes'            => 'nullable|string|max:5000',
        ]);

        if ($request->hasFile('receipt')) {
            // Delete old receipt if exists
            if ($expense->receipt_path) {
                Storage::disk('local')->delete($expense->receipt_path);
            }
            $validated['receipt_path'] = $request->file('receipt')->store('expenses', 'local');
        }

        unset($validated['receipt']);

        $expense->update($validated);

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted.');
    }

    /**
     * Download receipt file.
     */
    public function receipt(Expense $expense)
    {
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return Storage::disk('local')->download($expense->receipt_path);
    }
}
