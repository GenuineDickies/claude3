@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('service-requests.index') }}" class="hover:text-blue-600">All Tickets</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="hover:text-blue-600">SR #{{ $serviceRequest->id }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">{{ $workOrder->work_order_number }}</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-linear-to-r from-amber-50 to-white rounded-lg shadow-sm p-6 border-l-4 border-amber-500">
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $workOrder->work_order_number }}</h1>
                    @php
                        $statusColors = [
                            'pending'     => 'bg-amber-100 text-amber-700',
                            'in_progress' => 'bg-blue-100 text-blue-700',
                            'completed'   => 'bg-green-100 text-green-700',
                            'cancelled'   => 'bg-gray-100 text-gray-500',
                        ];
                        $priorityColors = [
                            'low'    => 'bg-gray-100 text-gray-600',
                            'normal' => 'bg-blue-50 text-blue-600',
                            'high'   => 'bg-orange-100 text-orange-700',
                            'urgent' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$workOrder->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ \App\Models\WorkOrder::STATUS_LABELS[$workOrder->status] ?? ucfirst($workOrder->status) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $priorityColors[$workOrder->priority] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ \App\Models\WorkOrder::PRIORITY_LABELS[$workOrder->priority] ?? ucfirst($workOrder->priority) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    For Service Request #{{ $serviceRequest->id }}
                    @if($serviceRequest->customer)
                        — {{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}
                    @endif
                </p>
                <p class="text-xs text-gray-400 mt-0.5">Created {{ $workOrder->created_at->format('M j, Y g:i A') }}</p>
            </div>

            <div class="flex gap-2 flex-wrap justify-end">
                {{-- Status transitions --}}
                @if(!in_array($workOrder->status, ['completed', 'cancelled']))
                    <form method="POST" action="{{ route('work-orders.update-status', [$serviceRequest, $workOrder]) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        @if($workOrder->status === 'pending')
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition">
                                Start Work
                            </button>
                        @elseif($workOrder->status === 'in_progress')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700 transition">
                                Mark Complete
                            </button>
                        @endif
                    </form>
                @endif

                {{-- Edit --}}
                @if(!in_array($workOrder->status, ['completed', 'cancelled']))
                    <a href="{{ route('work-orders.edit', [$serviceRequest, $workOrder]) }}"
                       class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-200 transition">
                        Edit
                    </a>
                @endif

                {{-- PDF --}}
                <a href="{{ route('work-orders.pdf', [$serviceRequest, $workOrder]) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download PDF
                </a>
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="block text-gray-500">Customer</span>
                @if($serviceRequest->customer)
                    <span class="font-medium">{{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}</span>
                    <span class="block text-gray-400 text-xs">{{ $serviceRequest->customer->phone }}</span>
                @else
                    <span class="text-gray-400 italic">No customer assigned</span>
                @endif
            </div>
            <div>
                <span class="block text-gray-500">Assigned To</span>
                <span class="font-medium">{{ $workOrder->assigned_to ?: 'Unassigned' }}</span>
            </div>
            <div>
                <span class="block text-gray-500">Priority</span>
                <span class="font-medium">{{ \App\Models\WorkOrder::PRIORITY_LABELS[$workOrder->priority] ?? ucfirst($workOrder->priority) }}</span>
            </div>
        </div>

        @if($workOrder->started_at || $workOrder->completed_at)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mt-4 pt-4 border-t border-gray-100">
                @if($workOrder->started_at)
                    <div>
                        <span class="block text-gray-500">Started</span>
                        <span class="font-medium">{{ $workOrder->started_at->format('M j, Y g:i A') }}</span>
                    </div>
                @endif
                @if($workOrder->completed_at)
                    <div>
                        <span class="block text-gray-500">Completed</span>
                        <span class="font-medium">{{ $workOrder->completed_at->format('M j, Y g:i A') }}</span>
                    </div>
                @endif
                @if($workOrder->started_at && $workOrder->completed_at)
                    <div>
                        <span class="block text-gray-500">Duration</span>
                        <span class="font-medium">{{ $workOrder->started_at->diffForHumans($workOrder->completed_at, true) }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if($workOrder->description)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <span class="block text-gray-500 text-sm mb-1">Description</span>
                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $workOrder->description }}</p>
            </div>
        @endif
    </div>

    {{-- Line Items --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">Work Order Items</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b border-gray-300">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Item</th>
                        <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-28">Price</th>
                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-20">Qty</th>
                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-20">Unit</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-28">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workOrder->items as $item)
                        <tr class="border-b border-gray-100 last:border-b-0 even:bg-gray-50/60">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800">{{ $item->name }}</span>
                                @if($item->description)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $item->description }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-gray-700">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="px-3 py-3 text-center text-gray-700">{{ $item->quantity + 0 }}</td>
                            <td class="px-3 py-3 text-center text-gray-500">{{ ucfirst($item->unit) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold text-gray-800">${{ number_format($item->lineTotal(), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totals --}}
        <div class="border-t-2 border-gray-200 bg-amber-50/60 px-6 py-4">
            <div class="flex flex-col items-end space-y-1.5 text-sm">
                <div class="flex justify-between w-56">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-mono font-medium text-gray-700">${{ number_format($workOrder->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between w-56">
                    <span class="text-gray-500">Tax ({{ $workOrder->tax_rate + 0 }}%)</span>
                    <span class="font-mono font-medium text-gray-700">${{ number_format($workOrder->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between w-56 border-t-2 border-amber-200 pt-2 mt-1">
                    <span class="font-bold text-gray-900">Total</span>
                    <span class="text-lg font-bold font-mono text-amber-700">${{ number_format($workOrder->total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($workOrder->notes || $workOrder->technician_notes)
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 space-y-4">
            @if($workOrder->notes)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $workOrder->notes }}</p>
                </div>
            @endif
            @if($workOrder->technician_notes)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Technician Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $workOrder->technician_notes }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Back link --}}
    <div class="flex justify-start">
        <a href="{{ route('service-requests.show', $serviceRequest) }}"
           class="text-sm text-gray-500 hover:text-blue-600 underline">&larr; Back to SR #{{ $serviceRequest->id }}</a>
    </div>
</div>
@endsection
