<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'service_request_id',
        'receipt_number',
        'customer_name',
        'customer_phone',
        'vehicle_description',
        'service_description',
        'service_location',
        'line_items',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'payment_method',
        'payment_reference',
        'payment_date',
        'notes',
        'issued_by',
        'company_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'line_items'       => 'array',
            'company_snapshot' => 'array',
            'subtotal'         => 'decimal:2',
            'tax_rate'         => 'decimal:4',
            'tax_amount'       => 'decimal:2',
            'total'            => 'decimal:2',
            'payment_date'     => 'date',
        ];
    }

    /**
     * Generate the next receipt number in format R-YYYYMMDD-XXXX.
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'R-' . now()->format('Ymd') . '-';

        $latest = static::where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $seq = 1;
        if ($latest) {
            $seq = ((int) substr($latest, -4)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
