@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header + Date Range Filter --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-white">Reports</h1>

        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">Period:</span>
            @foreach (['7' => '7 days', '30' => '30 days', '90' => '90 days', '365' => '1 year'] as $val => $label)
                <a href="{{ route('reports.dashboard', ['range' => $val]) }}"
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

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="surface-1 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Requests</p>
            <p class="text-2xl font-bold text-white mt-1">{{ number_format($totalRequests) }}</p>
        </div>
        <div class="surface-1 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Completed</p>
            <p class="text-2xl font-bold text-green-400 mt-1">{{ number_format($completedRequests) }}</p>
        </div>
        <div class="surface-1 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Cancelled</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ number_format($cancelledRequests) }}</p>
        </div>
        <div class="surface-1 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Revenue</p>
            <p class="text-2xl font-bold text-white mt-1">${{ number_format($totalRevenue, 2) }}</p>
        </div>
        <div class="surface-1 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Avg Response</p>
            <p class="text-2xl font-bold text-white mt-1">
                @if ($avgResponseMinutes !== null)
                    {{ $avgResponseMinutes < 60 ? number_format($avgResponseMinutes, 0) . ' min' : number_format($avgResponseMinutes / 60, 1) . ' hr' }}
                @else
                    <span class="text-gray-400">&mdash;</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Charts Row 1: Revenue + Requests Over Time --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="surface-1 p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">Revenue Over Time</h2>
            <div class="relative" style="height: 280px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        <div class="surface-1 p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">Requests Over Time</h2>
            <div class="relative" style="height: 280px;">
                <canvas id="requestsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Charts Row 2: By Status + Top Service Types --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="surface-1 p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">Requests by Status</h2>
            <div class="relative mx-auto" style="height: 280px; max-width: 320px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="surface-1 p-6">
            <h2 class="text-sm font-semibold text-gray-300 mb-4">Top Service Types</h2>
            <div class="relative" style="height: 280px;">
                <canvas id="serviceTypesChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Expenses Breakdown --}}
    @if (count($expensesByCategory) > 0)
    <div class="surface-1 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-300">Expenses by Category</h2>
            <p class="text-sm text-gray-500">Total: <span class="font-semibold text-gray-300">${{ number_format($totalExpenses, 2) }}</span></p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            @foreach ($expensesByCategory as $cat)
                <div class="surface-0 border border-white/10 rounded-md p-3 text-center">
                    <p class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $cat['category']) }}</p>
                    <p class="text-lg font-semibold text-white mt-1">${{ number_format($cat['total'], 2) }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fontFamily = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.family = fontFamily;
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.display = false;

    // ── Revenue Over Time ──────────────────────────────
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json($revenueByPeriod['labels']),
            datasets: [{
                data: @json($revenueByPeriod['data']),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: {{ count($revenueByPeriod['labels']) > 60 ? 0 : 2 }},
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } },
                y: { ticks: { callback: v => '$' + v.toLocaleString() }, beginAtZero: true },
            },
            plugins: { tooltip: { callbacks: { label: ctx => '$' + ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2 }) } } },
        }
    });

    // ── Requests Over Time ─────────────────────────────
    new Chart(document.getElementById('requestsChart'), {
        type: 'bar',
        data: {
            labels: @json($requestsByPeriod['labels']),
            datasets: [{
                data: @json($requestsByPeriod['data']),
                backgroundColor: '#6366f1',
                borderRadius: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
            },
        }
    });

    // ── Requests by Status (Doughnut) ──────────────────
    const statusData = @json($requestsByStatus);
    const statusLabels = {
        new: 'New', dispatched: 'Dispatched', en_route: 'En Route',
        on_scene: 'On Scene', completed: 'Completed', cancelled: 'Cancelled'
    };
    const statusColors = {
        new: '#3b82f6', dispatched: '#8b5cf6', en_route: '#f59e0b',
        on_scene: '#f97316', completed: '#22c55e', cancelled: '#ef4444'
    };

    const sLabels = Object.keys(statusData).map(k => statusLabels[k] || k);
    const sValues = Object.values(statusData);
    const sColors = Object.keys(statusData).map(k => statusColors[k] || '#9ca3af');

    if (sValues.length > 0) {
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: sLabels,
                datasets: [{ data: sValues, backgroundColor: sColors, borderWidth: 0 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                },
            }
        });
    }

    // ── Top Service Types (Horizontal Bar) ─────────────
    const serviceData = @json($topServiceTypes);
    if (serviceData.length > 0) {
        new Chart(document.getElementById('serviceTypesChart'), {
            type: 'bar',
            data: {
                labels: serviceData.map(d => d.name),
                datasets: [{
                    data: serviceData.map(d => d.count),
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
            }
        });
    }
});
</script>
@endpush
