<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use App\Models\ServiceRequest;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function dashboard(Request $request)
    {
        $range = $request->query('range', '30');
        $startDate = match ($range) {
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            '365' => now()->subDays(365)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };
        $endDate = now()->endOfDay();

        // ── Summary Cards ──────────────────────────────────────
        $totalRequests = ServiceRequest::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedRequests = ServiceRequest::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $cancelledRequests = ServiceRequest::where('status', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalRevenue = PaymentRecord::whereBetween('collected_at', [$startDate, $endDate])
            ->sum('amount');
        $totalExpenses = Expense::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        // ── Requests by Status ─────────────────────────────────
        $requestsByStatus = ServiceRequest::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ── Revenue Over Time (daily/weekly depending on range) ─
        $revenueByPeriod = $this->revenueByPeriod($startDate, $endDate, $range);

        // ── Top Service Types ──────────────────────────────────
        $topServiceTypes = ServiceRequest::whereBetween('service_requests.created_at', [$startDate, $endDate])
            ->whereNotNull('catalog_item_id')
            ->join('catalog_items', 'service_requests.catalog_item_id', '=', 'catalog_items.id')
            ->select('catalog_items.name', DB::raw('count(*) as count'))
            ->groupBy('catalog_items.name')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->toArray();

        // ── Average Response Time ──────────────────────────────
        // Time from created_at to first status change to 'en_route' or 'on_scene'
        $avgResponseMinutes = $this->averageResponseTime($startDate, $endDate);

        // ── Requests Over Time ─────────────────────────────────
        $requestsByPeriod = $this->requestsByPeriod($startDate, $endDate, $range);

        // ── Expense Breakdown by Category ──────────────────────
        $expensesByCategory = Expense::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('category', DB::raw('sum(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        return view('reports.dashboard', compact(
            'range',
            'startDate',
            'endDate',
            'totalRequests',
            'completedRequests',
            'cancelledRequests',
            'totalRevenue',
            'totalExpenses',
            'requestsByStatus',
            'revenueByPeriod',
            'topServiceTypes',
            'avgResponseMinutes',
            'requestsByPeriod',
            'expensesByCategory',
        ));
    }

    private function revenueByPeriod(Carbon $start, Carbon $end, string $range): array
    {
        $groupFormat = (int) $range > 90 ? '%Y-%m' : '%Y-%m-%d';
        $phpFormat = (int) $range > 90 ? 'Y-m' : 'Y-m-d';

        $groupExpr = $this->dateGroupExpr('collected_at', $groupFormat);
        $rows = PaymentRecord::whereBetween('collected_at', [$start, $end])
            ->select(DB::raw("{$groupExpr} as period"), DB::raw('sum(amount) as total'))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period')
            ->toArray();

        // Fill gaps for all periods
        $labels = [];
        $data = [];
        $interval = (int) $range > 90 ? '1 month' : '1 day';
        $period = CarbonPeriod::create($start, $interval, $end);

        foreach ($period as $date) {
            $key = $date->format($phpFormat);
            $labels[] = (int) $range > 90 ? $date->format('M Y') : $date->format('M j');
            $data[] = round((float) ($rows[$key] ?? 0), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function requestsByPeriod(Carbon $start, Carbon $end, string $range): array
    {
        $groupFormat = (int) $range > 90 ? '%Y-%m' : '%Y-%m-%d';
        $phpFormat = (int) $range > 90 ? 'Y-m' : 'Y-m-d';

        $groupExpr = $this->dateGroupExpr('created_at', $groupFormat);
        $rows = ServiceRequest::whereBetween('created_at', [$start, $end])
            ->select(DB::raw("{$groupExpr} as period"), DB::raw('count(*) as count'))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('count', 'period')
            ->toArray();

        $labels = [];
        $data = [];
        $interval = (int) $range > 90 ? '1 month' : '1 day';
        $period = CarbonPeriod::create($start, $interval, $end);

        foreach ($period as $date) {
            $key = $date->format($phpFormat);
            $labels[] = (int) $range > 90 ? $date->format('M Y') : $date->format('M j');
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function averageResponseTime(Carbon $start, Carbon $end): ?float
    {
        // Use status logs to find first dispatch/en_route transition
        $diffExpr = $this->minuteDiffExpr('sr.created_at', 'sl.created_at');
        $avg = DB::table('service_requests as sr')
            ->join('service_request_status_logs as sl', 'sr.id', '=', 'sl.service_request_id')
            ->whereBetween('sr.created_at', [$start, $end])
            ->whereIn('sl.new_status', ['dispatched', 'en_route'])
            ->select(DB::raw("avg({$diffExpr}) as avg_minutes"))
            ->value('avg_minutes');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    // ── Enhanced Financial Dashboard ───────────────────────────

    public function financial(Request $request)
    {
        $range = $request->query('range', '30');
        $startDate = match ($range) {
            '1'   => now()->startOfDay(),
            '7'   => now()->subDays(7)->startOfDay(),
            '30'  => now()->subDays(30)->startOfDay(),
            '90'  => now()->subDays(90)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };
        $endDate = now()->endOfDay();

        // ── Financial Metric Cards ─────────────────────────────

        // Paid Revenue — actual cash collected in period
        $paidRevenue = PaymentRecord::whereBetween('collected_at', [$startDate, $endDate])
            ->sum('amount');

        // Outstanding A/R — unpaid invoice balances (total minus applied payments)
        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'overdue'])
            ->where('is_locked', false)
            ->withSum('paymentRecords', 'amount')
            ->get();

        $outstandingAR = $unpaidInvoices->sum(function ($inv) {
            return max(0, (float) $inv->total - (float) ($inv->payment_records_sum_amount ?? 0));
        });

        // Overdue A/R — invoice balances past due date
        $today = now()->toDateString();
        $overdueAR = $unpaidInvoices->filter(function ($inv) use ($today) {
            if ($inv->status === 'overdue') return true;
            return $inv->status === 'sent' && $inv->due_date && $inv->due_date < $today;
        })->sum(function ($inv) {
            return max(0, (float) $inv->total - (float) ($inv->payment_records_sum_amount ?? 0));
        });

        // Total Billed in period — all non-draft/cancelled invoices created in period
        $totalBilled = Invoice::whereIn('status', ['sent', 'paid', 'overdue'])
            ->where('is_locked', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        // Collections Rate — (paid / billed) × 100%
        $collectionsRate = $totalBilled > 0
            ? round(($paidRevenue / $totalBilled) * 100, 1)
            : null;

        // Avg Ticket Value — average payment per paid invoice
        $paidInvoiceCount = Invoice::where('status', 'paid')
            ->where('is_locked', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $paidInvoiceRevenue = Invoice::where('status', 'paid')
            ->where('is_locked', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');
        $avgTicketValue = $paidInvoiceCount > 0
            ? round($paidInvoiceRevenue / $paidInvoiceCount, 2)
            : null;

        // Total Expenses in period
        $totalExpenses = Expense::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        // Margin Proxy — (revenue - expenses) / revenue × 100%
        $marginProxy = $paidRevenue > 0
            ? round((($paidRevenue - $totalExpenses) / $paidRevenue) * 100, 1)
            : null;

        // ── A/R Aging Buckets ──────────────────────────────────
        $agingBuckets = $this->arAgingBuckets();

        // ── 14-Day Trend Charts ────────────────────────────────
        $trendStart = now()->subDays(13)->startOfDay();
        $trendEnd = now()->endOfDay();
        $trendData = $this->dailyTrends($trendStart, $trendEnd);

        // ── Service Mix by Type ────────────────────────────────
        $serviceMix = ServiceRequest::whereBetween('service_requests.created_at', [$startDate, $endDate])
            ->whereNotNull('catalog_item_id')
            ->join('catalog_items', 'service_requests.catalog_item_id', '=', 'catalog_items.id')
            ->select('catalog_items.name', DB::raw('count(*) as count'))
            ->groupBy('catalog_items.name')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->toArray();

        // ── Operational Metrics ────────────────────────────────
        $totalRequestsInRange = ServiceRequest::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedInRange = ServiceRequest::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $completionRate = $totalRequestsInRange > 0
            ? round(($completedInRange / $totalRequestsInRange) * 100, 1)
            : null;

        // Open requests queue — all time (current state)
        $openQueue = ServiceRequest::whereIn('status', ['new', 'dispatched', 'en_route', 'on_scene'])->count();

        // Urgent unassigned — work orders with urgent priority and no assignment
        $urgentUnassigned = WorkOrder::where('priority', 'urgent')
            ->whereIn('status', ['pending'])
            ->where(function ($q) {
                $q->whereNull('assigned_to')->orWhere('assigned_to', '');
            })
            ->count();

        // ── Top Technicians ────────────────────────────────────
        $topTechnicians = $this->topTechnicians($startDate, $endDate);

        return view('reports.financial', compact(
            'range',
            'startDate',
            'endDate',
            'paidRevenue',
            'outstandingAR',
            'overdueAR',
            'collectionsRate',
            'avgTicketValue',
            'totalExpenses',
            'marginProxy',
            'totalBilled',
            'agingBuckets',
            'trendData',
            'serviceMix',
            'completionRate',
            'openQueue',
            'urgentUnassigned',
            'topTechnicians',
            'totalRequestsInRange',
            'completedInRange',
        ));
    }

    private function arAgingBuckets(): array
    {
        // Get all unpaid invoices with their applied payments
        $invoices = Invoice::whereIn('status', ['sent', 'overdue'])
            ->where('is_locked', false)
            ->whereNotNull('due_date')
            ->withSum('paymentRecords', 'amount')
            ->get();

        $buckets = [
            'current' => 0,
            '1_30'    => 0,
            '31_60'   => 0,
            '61_plus' => 0,
        ];

        foreach ($invoices as $invoice) {
            $balance = max(0, (float) $invoice->total - (float) ($invoice->payment_records_sum_amount ?? 0));
            if ($balance <= 0) continue;

            $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(now(), false);

            if ($daysOverdue <= 0) {
                $buckets['current'] += $balance;
            } elseif ($daysOverdue <= 30) {
                $buckets['1_30'] += $balance;
            } elseif ($daysOverdue <= 60) {
                $buckets['31_60'] += $balance;
            } else {
                $buckets['61_plus'] += $balance;
            }
        }

        return $buckets;
    }

    private function dailyTrends(Carbon $start, Carbon $end): array
    {
        $dayExprCreated = $this->dateGroupExpr('created_at', '%Y-%m-%d');
        $dayExprCollected = $this->dateGroupExpr('collected_at', '%Y-%m-%d');

        // Jobs received per day
        $received = ServiceRequest::whereBetween('created_at', [$start, $end])
            ->select(DB::raw("{$dayExprCreated} as day"), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Jobs completed per day
        $completedRows = DB::table('service_request_status_logs')
            ->whereBetween('created_at', [$start, $end])
            ->where('new_status', 'completed')
            ->select(DB::raw("{$dayExprCreated} as day"), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Revenue received per day
        $revenueRows = PaymentRecord::whereBetween('collected_at', [$start, $end])
            ->select(DB::raw("{$dayExprCollected} as day"), DB::raw('sum(amount) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->toArray();

        // Fill gaps
        $labels = [];
        $jobsReceived = [];
        $jobsCompleted = [];
        $revenueReceived = [];

        $period = CarbonPeriod::create($start, '1 day', $end);
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $jobsReceived[] = (int) ($received[$key] ?? 0);
            $jobsCompleted[] = (int) ($completedRows[$key] ?? 0);
            $revenueReceived[] = round((float) ($revenueRows[$key] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'jobsReceived' => $jobsReceived,
            'jobsCompleted' => $jobsCompleted,
            'revenueReceived' => $revenueReceived,
        ];
    }

    private function topTechnicians(Carbon $start, Carbon $end): array
    {
        return WorkOrder::whereBetween('work_orders.completed_at', [$start, $end])
            ->where('work_orders.status', 'completed')
            ->whereNotNull('assigned_to')
            ->where('assigned_to', '!=', '')
            ->select(
                'assigned_to as name',
                DB::raw('count(*) as jobs'),
                DB::raw('sum(total) as revenue'),
            )
            ->groupBy('assigned_to')
            ->orderByDesc('jobs')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /** Return a DB-driver-appropriate date-grouping expression. */
    private function dateGroupExpr(string $column, string $format): string
    {
        if (DB::getDriverName() === 'mysql') {
            return "DATE_FORMAT({$column}, '{$format}')";
        }

        return "strftime('{$format}', {$column})";
    }

    /** Return a DB-driver-appropriate minute-difference expression. */
    private function minuteDiffExpr(string $startCol, string $endCol): string
    {
        if (DB::getDriverName() === 'mysql') {
            return "TIMESTAMPDIFF(MINUTE, {$startCol}, {$endCol})";
        }

        return "(julianday({$endCol}) - julianday({$startCol})) * 24 * 60";
    }
}
