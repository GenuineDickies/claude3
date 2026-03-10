<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Presents read-only accounting and financial reporting views built from posted journal data.
 */
class AccountingController extends Controller
{
    public function __construct(
        private FinancialReportingService $reporting,
    ) {}

    /**
     * Show active general-scope accounts grouped by type for the chart of accounts page.
     */
    public function chartOfAccounts()
    {
        $accounts = Account::general()->where('is_active', true)->orderBy('code')->get();

        // Group by type for display
        $grouped = $accounts->groupBy('type');

        return view('accounting.chart-of-accounts', compact('accounts', 'grouped'));
    }

    /**
     * Show import-scope accounts used by document transaction import workflows.
     */
    public function importAccounts()
    {
        $accounts = Account::import()->where('is_active', true)->orderBy('code')->get();
        $grouped = $accounts->groupBy('type');

        return view('accounting.import-accounts', compact('accounts', 'grouped'));
    }

    /**
     * Show journal entries with status, date, and free-text filtering.
     */
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

    /**
     * Render the trial balance as of the requested day-end timestamp.
     */
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

    /**
     * Render the profit-and-loss statement for the requested date range.
     */
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

    /**
     * Render the balance sheet as of the requested date.
     */
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

    /**
     * Render the general ledger activity for one account over the requested date range.
     */
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
