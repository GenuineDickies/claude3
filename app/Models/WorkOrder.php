<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        'pending'     => 'Pending',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public const PRIORITY_LABELS = [
        'low'    => 'Low',
        'normal' => 'Normal',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'service_request_id',
        'estimate_id',
        'work_order_number',
        'status',
        'priority',
        'description',
        'notes',
        'technician_notes',
        'assigned_to',
        'started_at',
        'completed_at',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate'     => 'decimal:4',
            'subtotal'     => 'decimal:2',
            'tax_amount'   => 'decimal:2',
            'total'        => 'decimal:2',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Generate the next work order number in format WO-YYYYMMDD-XXXX.
     */
    public static function generateWorkOrderNumber(): string
    {
        $prefix = 'WO-' . now()->format('Ymd') . '-';

        $latest = static::where('work_order_number', 'like', $prefix . '%')
            ->orderByDesc('work_order_number')
            ->value('work_order_number');

        $seq = 1;
        if ($latest) {
            $seq = ((int) substr($latest, -4)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum(
            \Illuminate\Support\Facades\DB::raw('unit_price * quantity')
        );

        $taxAmount = round($subtotal * ($this->tax_rate / 100), 2);

        $this->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => $subtotal + $taxAmount,
        ]);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
