<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Work Order {{ $workOrder->work_order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .page { padding: 40px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header-left { display: table-cell; vertical-align: top; width: 55%; }
        .header-right { display: table-cell; vertical-align: top; width: 45%; text-align: right; }
        .company-name { font-size: 20px; font-weight: bold; color: #111; margin-bottom: 4px; }
        .company-detail { font-size: 10px; color: #555; }
        .wo-title { font-size: 22px; font-weight: bold; color: #333; }
        .wo-meta { font-size: 10px; color: #555; margin-top: 4px; }
        .status-badge { display: inline-block; padding: 2px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 3px; margin-top: 6px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
        .priority-badge { display: inline-block; padding: 2px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 3px; margin-top: 4px; }
        .priority-low { background: #e2e8f0; color: #475569; }
        .priority-normal { background: #dbeafe; color: #2563eb; }
        .priority-high { background: #fef3c7; color: #d97706; }
        .priority-urgent { background: #fee2e2; color: #b91c1c; }

        /* Sections */
        .section { margin-bottom: 20px; }
        .section-title { font-size: 12px; font-weight: bold; color: #555; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 8px; }

        /* Info grid */
        .info-grid { display: table; width: 100%; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
        .info-label { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: 0.3px; }
        .info-value { font-size: 11px; font-weight: 600; margin-bottom: 6px; }

        /* Line items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background: #f5f5f5; padding: 8px 6px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; color: #555; border-bottom: 2px solid #ddd; }
        .items-table td { padding: 7px 6px; border-bottom: 1px solid #eee; }
        .items-table .text-right { text-align: right; }
        .items-table .item-name { font-weight: 600; }
        .items-table .item-desc { color: #666; font-size: 10px; }

        /* Totals */
        .totals { width: 250px; margin-left: auto; }
        .totals-row { display: table; width: 100%; margin-bottom: 3px; }
        .totals-label { display: table-cell; text-align: left; color: #555; padding: 3px 0; }
        .totals-value { display: table-cell; text-align: right; padding: 3px 0; }
        .totals-total { border-top: 2px solid #333; font-size: 14px; font-weight: bold; margin-top: 5px; padding-top: 5px; }

        /* Notes */
        .notes-box { background: #f9f9f9; padding: 10px 12px; border-radius: 4px; }

        /* Footer */
        .footer { margin-top: 40px; text-align: center; border-top: 1px solid #ddd; padding-top: 15px; color: #888; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $company['name'] ?? '' }}</div>
            @if ($company['address'] ?? '')
                <div class="company-detail">{!! nl2br(e($company['address'])) !!}</div>
            @endif
            @if ($company['phone'] ?? '')
                <div class="company-detail">{{ $company['phone'] }}</div>
            @endif
            @if ($company['email'] ?? '')
                <div class="company-detail">{{ $company['email'] }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="wo-title">WORK ORDER</div>
            <div class="wo-meta">
                {{ $workOrder->work_order_number }}<br>
                Date: {{ $workOrder->created_at->format('F j, Y') }}
            </div>
            <span class="status-badge status-{{ $workOrder->status }}">{{ \App\Models\WorkOrder::STATUS_LABELS[$workOrder->status] ?? ucfirst($workOrder->status) }}</span>
            <br>
            <span class="priority-badge priority-{{ $workOrder->priority }}">{{ \App\Models\WorkOrder::PRIORITY_LABELS[$workOrder->priority] ?? ucfirst($workOrder->priority) }}</span>
        </div>
    </div>

    {{-- Customer & Job Info --}}
    <div class="section">
        <div class="info-grid">
            <div class="info-col">
                @if ($workOrder->serviceRequest->customer)
                    <div class="info-label">Customer</div>
                    <div class="info-value">{{ $workOrder->serviceRequest->customer->first_name }} {{ $workOrder->serviceRequest->customer->last_name }}</div>
                    @if ($workOrder->serviceRequest->customer->phone)
                        <div class="info-label">Phone</div>
                        <div class="info-value">{{ $workOrder->serviceRequest->customer->phone }}</div>
                    @endif
                @endif
                @if ($workOrder->assigned_to)
                    <div class="info-label">Assigned To</div>
                    <div class="info-value">{{ $workOrder->assigned_to }}</div>
                @endif
            </div>
            <div class="info-col">
                @if ($workOrder->serviceRequest->vehicle)
                    <div class="info-label">Vehicle</div>
                    <div class="info-value">
                        {{ $workOrder->serviceRequest->vehicle->year }}
                        {{ $workOrder->serviceRequest->vehicle->make }}
                        {{ $workOrder->serviceRequest->vehicle->model }}
                        @if ($workOrder->serviceRequest->vehicle->color)
                            ({{ $workOrder->serviceRequest->vehicle->color }})
                        @endif
                    </div>
                @endif
                @if ($workOrder->serviceRequest->catalogItem)
                    <div class="info-label">Service Type</div>
                    <div class="info-value">{{ $workOrder->serviceRequest->catalogItem->name }}</div>
                @endif
                @if ($workOrder->serviceRequest->location_address)
                    <div class="info-label">Location</div>
                    <div class="info-value">{{ $workOrder->serviceRequest->location_address }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Timestamps --}}
    @if ($workOrder->started_at || $workOrder->completed_at)
    <div class="section">
        <div class="section-title">Timeline</div>
        <div class="info-grid">
            <div class="info-col">
                @if ($workOrder->started_at)
                    <div class="info-label">Started</div>
                    <div class="info-value">{{ $workOrder->started_at->format('M j, Y g:i A') }}</div>
                @endif
            </div>
            <div class="info-col">
                @if ($workOrder->completed_at)
                    <div class="info-label">Completed</div>
                    <div class="info-value">{{ $workOrder->completed_at->format('M j, Y g:i A') }}</div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Description --}}
    @if ($workOrder->description)
    <div class="section">
        <div class="section-title">Description</div>
        <p>{!! nl2br(e($workOrder->description)) !!}</p>
    </div>
    @endif

    {{-- Line Items --}}
    <div class="section">
        <div class="section-title">Work Items</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th>Unit</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($workOrder->items as $item)
                <tr>
                    <td>
                        <span class="item-name">{{ $item->name }}</span>
                        @if ($item->description)
                            <br><span class="item-desc">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity + 0 }}</td>
                    <td>{{ $item->unit ?? '' }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->lineTotal(), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">${{ number_format($workOrder->subtotal, 2) }}</span>
            </div>
            @if ($workOrder->tax_rate > 0)
            <div class="totals-row">
                <span class="totals-label">Tax ({{ $workOrder->tax_rate + 0 }}%)</span>
                <span class="totals-value">${{ number_format($workOrder->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row totals-total">
                <span class="totals-label">Total</span>
                <span class="totals-value">${{ number_format($workOrder->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if ($workOrder->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <div class="notes-box">
            {!! nl2br(e($workOrder->notes)) !!}
        </div>
    </div>
    @endif

    {{-- Technician Notes --}}
    @if ($workOrder->technician_notes)
    <div class="section">
        <div class="section-title">Technician Notes</div>
        <div class="notes-box">
            {!! nl2br(e($workOrder->technician_notes)) !!}
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        {{ $company['name'] ?? '' }}
        @if ($company['phone'] ?? '')
            &bull; {{ $company['phone'] }}
        @endif
    </div>

</div>
</body>
</html>
