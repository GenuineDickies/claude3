<?php

namespace App\Models;

use App\Models\ChangeOrder;
use App\Models\Estimate;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int|null $estimate_id
 * @property string $work_order_number
 * @property string $status
 * @property string $priority
 * @property string|null $description
 * @property string|null $notes
 * @property string|null $technician_notes
 * @property string|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property numeric $subtotal
 * @property numeric $tax_rate
 * @property numeric $tax_amount
 * @property numeric $total
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ChangeOrder> $changeOrders
 * @property-read int|null $change_orders_count
 * @property-read User|null $creator
 * @property-read Estimate|null $estimate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkOrderItem> $items
 * @property-read int|null $items_count
 * @property-read ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereEstimateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereTechnicianNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereWorkOrderNumber($value)
 * @mixin \Eloquent
 */
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

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class)->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
