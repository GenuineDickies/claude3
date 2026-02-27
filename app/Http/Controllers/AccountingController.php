<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(
        private FinancialReportingService $reporting,
    ) {}

    // ── Chart of Accounts ──────────────────────────────

    public function chartOfAccounts()
    {
        $accounts = Account::orderBy('code')->get();

        // Group by type for display
        $grouped = $accounts->groupBy('type');

        return view('accounting.chart-of-accounts', compact('accounts', 'grouped'));
    }

    // ── Journal Entries ────────────────────────────────

    public function journal(Request $request)
    {
        $query = JournalEntry::with(['lines.account', 'creator'])->latest('entry_date')->latest('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('entry_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('entry_date', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                  ->orWhere('memo', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(25)->withQueryString();

        return view('accounting.journal', compact('entries'));
    }

    // ── Trial Balance ──────────────────────────────────

    public function trialBalance(Request $request)
    {
        $asOf = $request->input('as_of')
            ? Carbon::parse($request->input('as_of'))->endOfDay()
            : now()->endOfDay();

        $data = $this->reporting->trialBalance($asOf);

        return view('accounting.trial-balance', [
            'accounts'     => $data['accounts'],
            'totalDebits'  => $data['total_debits'],
            'totalCredits' => $data['total_credits'],
            'asOf'         => $asOf,
        ]);
    }

    // ── Profit & Loss ──────────────────────────────────

    public function profitAndLoss(Request $request)
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        $data = $this->reporting->profitAndLoss($from, $to);

        return view('accounting.profit-loss', array_merge($data, [
            'from' => $from,
            'to'   => $to,
        ]));
    }

    // ── Balance Sheet ──────────────────────────────────

    public function balanceSheet(Request $request)
    {
        $asOf = $request->input('as_of')
            ? Carbon::parse($request->input('as_of'))->endOfDay()
            : now()->endOfDay();

        $data = $this->reporting->balanceSheet($asOf);

        return view('accounting.balance-sheet', array_merge($data, [
            'asOf' => $asOf,
        ]));
    }

    // ── General Ledger ─────────────────────────────────

    public function generalLedger(Request $request, Account $account)
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        $data = $this->reporting->generalLedger($account, $from, $to);

        return view('accounting.general-ledger', array_merge($data, [
            'from' => $from,
            'to'   => $to,
        ]));
    }
}
