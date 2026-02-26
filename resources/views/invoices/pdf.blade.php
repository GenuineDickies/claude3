<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
        .invoice-title { font-size: 22px; font-weight: bold; color: #333; }
        .invoice-meta { font-size: 10px; color: #555; margin-top: 4px; }
        .status-badge { display: inline-block; padding: 2px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 3px; margin-top: 6px; }
        .status-draft { background: #e2e8f0; color: #475569; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-overdue { background: #fee2e2; color: #b91c1c; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }

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

        /* Payment terms */
        .terms-info { background: #f9f9f9; padding: 10px 12px; border-radius: 4px; }

        /* Footer */
        .footer { margin-top: 40px; text-align: center; border-top: 1px solid #ddd; padding-top: 15px; color: #888; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $invoice->company_snapshot['name'] ?? '' }}</div>
            @if ($invoice->company_snapshot['address'] ?? '')
                <div class="company-detail">{!! nl2br(e($invoice->company_snapshot['address'])) !!}</div>
            @endif
            @if ($invoice->company_snapshot['phone'] ?? '')
                <div class="company-detail">{{ $invoice->company_snapshot['phone'] }}</div>
            @endif
            @if ($invoice->company_snapshot['email'] ?? '')
                <div class="company-detail">{{ $invoice->company_snapshot['email'] }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-meta">
                {{ $invoice->invoice_number }}<br>
                Date: {{ $invoice->created_at->format('F j, Y') }}
                @if ($invoice->due_date)
                    <br>Due: {{ $invoice->due_date->format('F j, Y') }}
                @endif
            </div>
            <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
        </div>
    </div>

    {{-- Customer & Service Info --}}
    <div class="section">
        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Bill To</div>
                <div class="info-value">{{ $invoice->customer_name }}</div>
                @if ($invoice->customer_phone)
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $invoice->customer_phone }}</div>
                @endif
            </div>
            <div class="info-col">
                @if ($invoice->vehicle_description)
                    <div class="info-label">Vehicle</div>
                    <div class="info-value">{{ $invoice->vehicle_description }}</div>
                @endif
                @if ($invoice->service_description)
                    <div class="info-label">Service</div>
                    <div class="info-value">{{ $invoice->service_description }}</div>
                @endif
                @if ($invoice->service_location)
                    <div class="info-label">Location</div>
                    <div class="info-value">{{ $invoice->service_location }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="section">
        <div class="section-title">Services & Items</div>
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
                @foreach ($invoice->line_items as $item)
                <tr>
                    <td>
                        <span class="item-name">{{ $item['name'] }}</span>
                        @if ($item['description'] ?? '')
                            <br><span class="item-desc">{{ $item['description'] }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td>{{ $item['unit'] ?? '' }}</td>
                    <td class="text-right">${{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right">${{ number_format($item['quantity'] * $item['unit_price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">${{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if ($invoice->tax_rate > 0)
            <div class="totals-row">
                <span class="totals-label">Tax ({{ $invoice->tax_rate + 0 }}%)</span>
                <span class="totals-value">${{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row totals-total">
                <span class="totals-label">Total Due</span>
                <span class="totals-value">${{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Payment Terms --}}
    @if ($invoice->payment_terms)
    <div class="section">
        <div class="section-title">Payment Terms</div>
        <div class="terms-info">
            {{ $invoice->payment_terms }}
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if ($invoice->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <p>{!! nl2br(e($invoice->notes)) !!}</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        Thank you for your business!<br>
        {{ $invoice->company_snapshot['name'] ?? '' }}
    </div>

</div>
</body>
</html>
