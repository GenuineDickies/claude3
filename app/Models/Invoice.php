<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SENT      = 'sent';
    public const STATUS_PAID      = 'paid';
    public const STATUS_OVERDUE   = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'service_request_id',
        'invoice_number',
        'status',
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
        'due_date',
        'payment_terms',
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
            'due_date'         => 'date',
        ];
    }

    /**
     * Generate the next invoice number in format INV-YYYYMMDD-XXXX.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';

        $latest = static::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

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
