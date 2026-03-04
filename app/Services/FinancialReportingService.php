<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;

class FinancialReportingService
{
    /**
     * Trial Balance — all accounts with debit/credit totals.
     * Posted entries only. Debits must equal credits.
     *
     * @return array{accounts: array, total_debits: float, total_credits: float}
     */
    public function trialBalance(?\DateTimeInterface $asOf = null): array
    {
        $accounts = Account::general()->where('is_active', true)->orderBy('code')->get();

        $rows = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $query = JournalLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOf) {
                    $q->where('status', JournalEntry::STATUS_POSTED);
                    if ($asOf) {
                        $q->where('entry_date', '<=', $asOf);
                    }
                });

            $debits  = (float) (clone $query)->sum('debit');
            $credits = (float) (clone $query)->sum('credit');

            if ($debits === 0.0 && $credits === 0.0) {
                continue; // skip zero-activity accounts
            }

            $balance = $account->isDebitNormal()
                ? round($debits - $credits, 2)
                : round($credits - $debits, 2);

            // Express as debit or credit column for the TB
            $debitBalance = $account->isDebitNormal() ? abs($balance) : 0;
            $creditBalance = $account->isDebitNormal() ? 0 : abs($balance);

            if ($balance < 0) {
                // Contra balance — show on opposite side
                $debitBalance = $account->isDebitNormal() ? 0 : abs($balance);
                $creditBalance = $account->isDebitNormal() ? abs($balance) : 0;
            }

            $rows[] = [
                'code'    => $account->code,
                'name'    => $account->name,
                'type'    => $account->type,
                'debit'   => round($debitBalance, 2),
                'credit'  => round($creditBalance, 2),
                'balance' => $balance,
            ];

            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;
        }

        return [
            'accounts'      => $rows,
            'total_debits'  => round($totalDebits, 2),
            'total_credits' => round($totalCredits, 2),
        ];
    }

    /**
     * Profit & Loss (Income Statement) for a date range.
     *
     * Revenue - COGS - Expenses = Net Income
     *
     * @return array{revenue: array, cogs: array, expenses: array, total_revenue: float, total_cogs: float, total_expenses: float, gross_profit: float, net_income: float}
     */
    public function profitAndLoss(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $revenue  = $this->accountBalancesForRange(['revenue'], $from, $to);
        $cogs     = $this->accountBalancesForRange(['cogs'], $from, $to);
        $expenses = $this->accountBalancesForRange(['expense'], $from, $to);

        $totalRevenue  = array_sum(array_column($revenue, 'balance'));
        $totalCogs     = array_sum(array_column($cogs, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $grossProfit   = round($totalRevenue - $totalCogs, 2);
        $netIncome     = round($grossProfit - $totalExpenses, 2);

        return [
            'revenue'        => $revenue,
            'cogs'           => $cogs,
            'expenses'       => $expenses,
            'total_revenue'  => round($totalRevenue, 2),
            'total_cogs'     => round($totalCogs, 2),
            'total_expenses' => round($totalExpenses, 2),
            'gross_profit'   => $grossProfit,
            'net_income'     => $netIncome,
        ];
    }

    /**
     * Balance Sheet as of a given date.
     *
     * Assets = Liabilities + Equity + Net Income
     *
     * @return array{assets: array, liabilities: array, equity: array, total_assets: float, total_liabilities: float, total_equity: float, net_income: float, equity_plus_income: float}
     */
    public function balanceSheet(\DateTimeInterface $asOf): array
    {
        $assets      = $this->accountBalancesAsOf(['asset'], $asOf);
        $liabilities = $this->accountBalancesAsOf(['liability'], $asOf);
        $equity      = $this->accountBalancesAsOf(['equity'], $asOf);

        $totalAssets      = round(array_sum(array_column($assets, 'balance')), 2);
        $totalLiabilities = round(array_sum(array_column($liabilities, 'balance')), 2);
        $totalEquity      = round(array_sum(array_column($equity, 'balance')), 2);

        // Net income = Revenue − COGS − Expenses (all time up to asOf)
        // This feeds into Retained Earnings on the balance sheet
        $netIncome = $this->netIncomeAsOf($asOf);

        return [
            'assets'            => $assets,
            'liabilities'       => $liabilities,
            'equity'            => $equity,
            'total_assets'      => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity'      => $totalEquity,
            'net_income'        => $netIncome,
            'equity_plus_income' => round($totalLiabilities + $totalEquity + $netIncome, 2),
        ];
    }

    /**
     * General Ledger — all postings for a specific account in a date range.
     *
     * @return array{account: Account, entries: array, opening_balance: float}
     */
    public function generalLedger(Account $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // Opening balance = all posted lines before $from
        $openingQuery = JournalLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($from) {
                $q->where('status', JournalEntry::STATUS_POSTED)
                  ->where('entry_date', '<', $from);
            });

        $openDebits  = (float) (clone $openingQuery)->sum('debit');
        $openCredits = (float) (clone $openingQuery)->sum('credit');
        $openingBalance = $account->isDebitNormal()
            ? round($openDebits - $openCredits, 2)
            : round($openCredits - $openDebits, 2);

        // Lines in the date range
        $lines = JournalLine::with(['journalEntry', 'account'])
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($from, $to) {
                $q->where('status', JournalEntry::STATUS_POSTED)
                  ->whereBetween('entry_date', [$from, $to]);
            })
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->select('journal_lines.*')
            ->get();

        $running = $openingBalance;
        $entries = [];

        foreach ($lines as $line) {
            $je = $line->journalEntry;
            if ($account->isDebitNormal()) {
                $running = round($running + (float) $line->debit - (float) $line->credit, 2);
            } else {
                $running = round($running + (float) $line->credit - (float) $line->debit, 2);
            }

            $entries[] = [
                'date'           => $je->entry_date->toDateString(),
                'entry_number'   => $je->entry_number,
                'memo'           => $je->memo,
                'description'    => $line->description,
                'debit'          => (float) $line->debit,
                'credit'         => (float) $line->credit,
                'running_balance' => $running,
            ];
        }

        return [
            'account'         => $account,
            'entries'         => $entries,
            'opening_balance' => $openingBalance,
        ];
    }

    // ── Private helpers ────────────────────────────────

    /**
     * Get account balances for a specific date range (for P&L — revenue/expense accounts).
     * These are period accounts, so we only sum activity within the range.
     */
    private function accountBalancesForRange(array $types, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $accounts = Account::whereIn('type', $types)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = [];
        foreach ($accounts as $account) {
            $query = JournalLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($from, $to) {
                    $q->where('status', JournalEntry::STATUS_POSTED)
                      ->whereBetween('entry_date', [$from, $to]);
                });

            $debits  = (float) (clone $query)->sum('debit');
            $credits = (float) (clone $query)->sum('credit');

            $balance = $account->isDebitNormal()
                ? round($debits - $credits, 2)
                : round($credits - $debits, 2);

            if ($balance == 0) {
                continue;
            }

            $rows[] = [
                'code'    => $account->code,
                'name'    => $account->name,
                'balance' => $balance,
            ];
        }

        return $rows;
    }

    /**
     * Get account balances as of a date (cumulative — for BS accounts).
     */
    private function accountBalancesAsOf(array $types, \DateTimeInterface $asOf): array
    {
        $accounts = Account::general()->whereIn('type', $types)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = [];
        foreach ($accounts as $account) {
            $balance = $account->balance($asOf);
            if ($balance == 0) {
                continue;
            }

            $rows[] = [
                'code'    => $account->code,
                'name'    => $account->name,
                'balance' => $balance,
            ];
        }

        return $rows;
    }

    /**
     * Calculate net income from all posted entries up to a date.
     */
    private function netIncomeAsOf(\DateTimeInterface $asOf): float
    {
        $revenueAccounts = Account::general()->whereIn('type', ['revenue'])->pluck('id');
        $cogsAccounts = Account::general()->whereIn('type', ['cogs'])->pluck('id');
        $expenseAccounts = Account::general()->whereIn('type', ['expense'])->pluck('id');

        $postedCondition = function ($q) use ($asOf) {
            $q->where('status', JournalEntry::STATUS_POSTED)
              ->where('entry_date', '<=', $asOf);
        };

        $revenue = (float) JournalLine::whereIn('account_id', $revenueAccounts)
            ->whereHas('journalEntry', $postedCondition)
            ->selectRaw('SUM(credit) - SUM(debit) as net')
            ->value('net');

        $cogs = (float) JournalLine::whereIn('account_id', $cogsAccounts)
            ->whereHas('journalEntry', $postedCondition)
            ->selectRaw('SUM(debit) - SUM(credit) as net')
            ->value('net');

        $expenses = (float) JournalLine::whereIn('account_id', $expenseAccounts)
            ->whereHas('journalEntry', $postedCondition)
            ->selectRaw('SUM(debit) - SUM(credit) as net')
            ->value('net');

        return round($revenue - $cogs - $expenses, 2);
    }
}
