<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $receipt->receipt_number }}</title>
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
        .receipt-title { font-size: 22px; font-weight: bold; color: #333; }
        .receipt-meta { font-size: 10px; color: #555; margin-top: 4px; }

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

        /* Payment */
        .payment-info { background: #f9f9f9; padding: 10px 12px; border-radius: 4px; }

        /* Footer */
        .footer { margin-top: 40px; text-align: center; border-top: 1px solid #ddd; padding-top: 15px; color: #888; font-size: 10px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $receipt->company_snapshot['name'] ?? '' }}</div>
            @if ($receipt->company_snapshot['address'] ?? '')
                <div class="company-detail">{!! nl2br(e($receipt->company_snapshot['address'])) !!}</div>
            @endif
            @if ($receipt->company_snapshot['phone'] ?? '')
                <div class="company-detail">{{ $receipt->company_snapshot['phone'] }}</div>
            @endif
            @if ($receipt->company_snapshot['email'] ?? '')
                <div class="company-detail">{{ $receipt->company_snapshot['email'] }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="receipt-title">RECEIPT</div>
            <div class="receipt-meta">
                {{ $receipt->receipt_number }}<br>
                Date: {{ $receipt->created_at->format('F j, Y') }}
            </div>
        </div>
    </div>

    {{-- Customer & Service Info --}}
    <div class="section">
        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Customer</div>
                <div class="info-value">{{ $receipt->customer_name }}</div>
                @if ($receipt->customer_phone)
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $receipt->customer_phone }}</div>
                @endif
            </div>
            <div class="info-col">
                @if ($receipt->vehicle_description)
                    <div class="info-label">Vehicle</div>
                    <div class="info-value">{{ $receipt->vehicle_description }}</div>
                @endif
                @if ($receipt->service_description)
                    <div class="info-label">Service</div>
                    <div class="info-value">{{ $receipt->service_description }}</div>
                @endif
                @if ($receipt->service_location)
                    <div class="info-label">Location</div>
                    <div class="info-value">{{ $receipt->service_location }}</div>
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
                @foreach ($receipt->line_items as $item)
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
                <span class="totals-value">${{ number_format($receipt->subtotal, 2) }}</span>
            </div>
            @if ($receipt->tax_rate > 0)
            <div class="totals-row">
                <span class="totals-label">Tax ({{ $receipt->tax_rate + 0 }}%)</span>
                <span class="totals-value">${{ number_format($receipt->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row totals-total">
                <span class="totals-label">Total</span>
                <span class="totals-value">${{ number_format($receipt->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Payment --}}
    @if ($receipt->payment_method)
    <div class="section">
        <div class="section-title">Payment</div>
        <div class="payment-info">
            Method: <strong>{{ ucfirst($receipt->payment_method) }}</strong>
            @if ($receipt->payment_reference)
                &nbsp;&middot;&nbsp; Ref: {{ $receipt->payment_reference }}
            @endif
            @if ($receipt->payment_date)
                &nbsp;&middot;&nbsp; Date: {{ $receipt->payment_date->format('M j, Y') }}
            @endif
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if ($receipt->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <p>{!! nl2br(e($receipt->notes)) !!}</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        Thank you for your business!<br>
        {{ $receipt->company_snapshot['name'] ?? '' }}
    </div>

</div>
</body>
</html>
