@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header + Date Range Selector --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-white">Financial Dashboard</h1>

        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">Period:</span>
            @foreach (['1' => 'Today', '7' => '7 days', '30' => '30 days', '90' => '90 days'] as $val => $label)
                <a href="{{ route('reports.financial', ['range' => $val]) }}"
                   @class([
                       'px-3 py-1.5 text-sm font-medium rounded-md transition min-h-[44px] inline-flex items-center',
                       'btn-crystal' => $range == $val,
                       'btn-crystal-secondary' => $range != $val,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ═══ Financial Metric Cards ═══ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- Paid Revenue --}}
        <div class="stat-card stat-card--default">
            <p class="stat-card__label">Paid Revenue</p>
            <p class="stat-card__value">${{ number_format($paidRevenue, 2) }}</p>
            <p class="stat-card__sub">Collected in period</p>
        </div>

        {{-- Outstanding A/R --}}
        <div class="stat-card {{ $outstandingAR > 0 ? 'stat-card--warn' : 'stat-card--default' }}">
            <p class="stat-card__label">Outstanding A/R</p>
            <p class="stat-card__value">${{ number_format($outstandingAR, 2) }}</p>
            <p class="stat-card__sub">Unpaid invoices</p>
        </div>

        {{-- Overdue A/R --}}
        <div class="stat-card {{ $overdueAR > 0 ? 'stat-card--danger' : 'stat-card--default' }}">
            <p class="stat-card__label">Overdue A/R</p>
            <p class="stat-card__value">${{ number_format($overdueAR, 2) }}</p>
            <p class="stat-card__sub">Past due date</p>
        </div>

        {{-- Collections Rate --}}
        <div class="stat-card {{ $collectionsRate !== null && $collectionsRate >= 80 ? 'stat-card--good' : 'stat-card--default' }}">
            <p class="stat-card__label">Collections Rate</p>
            <p class="stat-card__value">{{ $collectionsRate !== null ? $collectionsRate . '%' : '—' }}</p>
            <p class="stat-card__sub">Paid / billed</p>
        </div>

        {{-- Avg Ticket Value --}}
        <div class="stat-card stat-card--default">
            <p class="stat-card__label">Avg Ticket Value</p>
            <p class="stat-card__value">{{ $avgTicketValue !== null ? '$' . number_format($avgTicketValue, 2) : '—' }}</p>
            <p class="stat-card__sub">Per paid invoice</p>
        </div>

        {{-- Margin Proxy --}}
        <div class="stat-card {{ $marginProxy !== null && $marginProxy >= 50 ? 'stat-card--good' : ($marginProxy !== null && $marginProxy < 20 ? 'stat-card--danger' : 'stat-card--default') }}">
            <p class="stat-card__label">Margin Proxy</p>
            <p class="stat-card__value">{{ $marginProxy !== null ? $marginProxy . '%' : '—' }}</p>
            <p class="stat-card__sub">(Revenue − Expenses) / Revenue</p>
        </div>
    </div>

    {{-- ═══ A/R Aging Buckets ═══ --}}
    <div class="surface-1 p-6">
        <h2 class="text-sm font-semibold text-gray-300 mb-4">Accounts Receivable Aging</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="border-l-4 border-green-500 bg-green-500/10 rounded-r-md p-4">
                <p class="text-xs font-medium text-gray-500 uppercase">Current</p>
                <p class="text-xl font-bold text-white mt-1 font-mono">${{ number_format($agingBuckets['current'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Not yet due</p>
            </div>
            <div class="border-l-4 border-yellow-500 bg-yellow-50 rounded-r-md p-4">
                <p class="text-xs font-medium text-gray-500 uppercase">1–30 Days</p>
                <p class="text-xl font-bold text-white mt-1 font-mono">${{ number_format($agingBuckets['1_30'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Slightly overdue</p>
            </div>
            <div class="border-l-4 border-orange-500 bg-orange-50 rounded-r-md p-4">
                <p class="text-xs font-medium text-gray-500 uppercase">31–60 Days</p>
                <p class="text-xl font-bold text-white mt-1 font-mono">${{ number_format($agingBuckets['31_60'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Needs follow-up</p>
            </div>
            <div class="border-l-4 border-red-500 bg-red-50 rounded-r-md p-4">
                <p class="text-xs font-medium text-gray-500 uppercase">61+ Days</p>
                <p class="text-xl font-bold text-white mt-1 font-mono">${{ number_format($agingBuckets['61_plus'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">At risk</p>
            </div>
        </div>
    </div>

    {{-- ═══ 14-Day Trend Charts ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="surface-1 p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">Jobs — 14 Day Trend</h2>
            <div class="relative" style="height: 260px;">
                <canvas id="jobsTrendChart"></canvas>
            </div>
        </div>
        <div class="surface-1 p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">Revenue — 14 Day Trend</h2>
            <div class="relative" style="height: 260px;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ═══ Operational Metrics + Service Mix ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Operational Metrics --}}
        <div class="surface-1 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-300">Operational Metrics</h2>
            <div class="grid grid-cols-3 gap-3">
                <div class="text-center p-3 surface-0 rounded-md border border-white/10">
                    <p class="text-xs text-gray-500">Completion Rate</p>
                    <p class="text-xl font-bold text-white mt-1">{{ $completionRate !== null ? $completionRate . '%' : '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $completedInRange }} / {{ $totalRequestsInRange }}</p>
                </div>
                <div class="text-center p-3 surface-0 rounded-md border border-white/10">
                    <p class="text-xs text-gray-500">Open Queue</p>
                    <p class="text-xl font-bold {{ $openQueue > 10 ? 'text-red-400' : 'text-white' }} mt-1">{{ $openQueue }}</p>
                    <p class="text-xs text-gray-400">Active requests</p>
                </div>
                <div class="text-center p-3 surface-0 rounded-md border border-white/10">
                    <p class="text-xs text-gray-500">Urgent Unassigned</p>
                    <p class="text-xl font-bold {{ $urgentUnassigned > 0 ? 'text-red-400' : 'text-green-400' }} mt-1">{{ $urgentUnassigned }}</p>
                    <p class="text-xs text-gray-400">Needs attention</p>
                </div>
            </div>
        </div>

        {{-- Service Mix by Type --}}
        <div class="surface-1 p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">Service Mix</h2>
            @if (count($serviceMix) > 0)
                <div class="relative" style="height: 220px;">
                    <canvas id="serviceMixChart"></canvas>
                </div>
            @else
                <p class="text-sm text-gray-400 py-8 text-center">No service data in period</p>
            @endif
        </div>
    </div>

    {{-- ═══ Top Technicians ═══ --}}
    @if (count($topTechnicians) > 0)
    <div class="surface-1 p-6">
        <h2 class="text-sm font-semibold text-gray-300 mb-4">Top Technicians</h2>
        <div class="overflow-x-auto">
            <table class="table-crystal w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-white/10">
                        <th class="pb-2 pr-4">#</th>
                        <th class="pb-2 pr-4">Technician</th>
                        <th class="pb-2 pr-4 text-right">Jobs</th>
                        <th class="pb-2 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($topTechnicians as $i => $tech)
                        <tr>
                            <td class="py-2 pr-4 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                            <td class="py-2 pr-4 font-medium text-white">{{ $tech['name'] }}</td>
                            <td class="py-2 pr-4 text-right tabular-nums">{{ $tech['jobs'] }}</td>
                            <td class="py-2 text-right tabular-nums font-medium">${{ number_format($tech['revenue'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

<style>
    .stat-card {
        @apply surface-1 p-5 border-l-4 transition hover:-translate-y-0.5 hover:shadow-md;
    }
    .stat-card--default { @apply border-blue-900; }
    .stat-card--good    { @apply border-green-500; }
    .stat-card--warn    { @apply border-amber-500; }
    .stat-card--danger  { @apply border-red-500; }
    .stat-card__label   { @apply text-xs font-medium text-gray-500 uppercase tracking-wide; }
    .stat-card__value   { @apply text-2xl font-bold text-white mt-1 font-mono tabular-nums; }
    .stat-card__sub     { @apply text-xs text-gray-400 mt-0.5; }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;

    const trendLabels = @json($trendData['labels']);

    // ── Jobs 14-Day Trend (dual line) ──────────────────
    new Chart(document.getElementById('jobsTrendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label: 'Received',
                    data: @json($trendData['jobsReceived']),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                },
                {
                    label: 'Completed',
                    data: @json($trendData['jobsCompleted']),
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { maxTicksLimit: 7 }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
            },
            plugins: { legend: { display: true, position: 'top' } },
        }
    });

    // ── Revenue 14-Day Trend (bar) ─────────────────────
    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'bar',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Revenue',
                data: @json($trendData['revenueReceived']),
                backgroundColor: '#6366f1',
                borderRadius: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { maxTicksLimit: 7 }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString() } },
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => '$' + ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
            },
        }
    });

    // ── Service Mix (horizontal bar) ───────────────────
    const serviceMix = @json($serviceMix);
    if (serviceMix.length > 0) {
        new Chart(document.getElementById('serviceMixChart'), {
            type: 'bar',
            data: {
                labels: serviceMix.map(d => d.name),
                datasets: [{
                    data: serviceMix.map(d => d.count),
                    backgroundColor: '#0ea5e9',
                    borderRadius: 3,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } },
                    y: { grid: { display: false } },
                },
                plugins: { legend: { display: false } },
            }
        });
    }
});
</script>
@endpush
