<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int|null $work_order_id
 * @property string $invoice_number
 * @property string $status
 * @property int $version
 * @property bool $is_locked
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property int|null $parent_version_id
 * @property string $customer_name
 * @property string|null $customer_phone
 * @property string|null $vehicle_description
 * @property string|null $service_description
 * @property string|null $service_location
 * @property array<array-key, mixed> $line_items
 * @property numeric $subtotal
 * @property numeric $tax_rate
 * @property numeric $tax_amount
 * @property numeric $total
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property string|null $payment_terms
 * @property string|null $notes
 * @property int|null $issued_by
 * @property array<array-key, mixed> $company_snapshot
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, Invoice> $childVersions
 * @property-read int|null $child_versions_count
 * @property-read \App\Models\User|null $issuedBy
 * @property-read Invoice|null $parentVersion
 * @property-read Collection<int, \App\Models\PaymentRecord> $paymentRecords
 * @property-read int|null $payment_records_count
 * @property-read Collection<int, \App\Models\Receipt> $receipts
 * @property-read int|null $receipts_count
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @property-read \App\Models\WorkOrder|null $workOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCompanySnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCustomerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIssuedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereLineItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereLockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereParentVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaymentTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereServiceDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereServiceLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVehicleDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereWorkOrderId($value)
 * @mixin \Eloquent
 */
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

    /** Allowed status transitions: current => [allowed next statuses]. */
    public const TRANSITIONS = [
        self::STATUS_DRAFT     => [self::STATUS_SENT, self::STATUS_CANCELLED],
        self::STATUS_SENT      => [self::STATUS_PAID, self::STATUS_OVERDUE, self::STATUS_CANCELLED],
        self::STATUS_OVERDUE   => [self::STATUS_PAID, self::STATUS_CANCELLED],
        self::STATUS_PAID      => [],
        self::STATUS_CANCELLED => [],
    ];

    /** Statuses from which a new revision may be created. */
    public const REVISABLE_STATUSES = [self::STATUS_SENT, self::STATUS_OVERDUE];

    protected $attributes = [
        'version'   => 1,
        'is_locked' => false,
    ];

    protected $fillable = [
        'service_request_id',
        'work_order_id',
        'invoice_number',
        'status',
        'version',
        'is_locked',
        'locked_at',
        'parent_version_id',
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
            'is_locked'        => 'boolean',
            'locked_at'        => 'datetime',
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

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    public function childVersions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_version_id');
    }

    /** Get all versions sharing the same invoice_number, ordered by version. */
    public function allVersions(): Collection
    {
        return self::where('invoice_number', $this->invoice_number)
            ->orderBy('version')
            ->get();
    }

    public function isEditable(): bool
    {
        return !$this->is_locked;
    }

    public function lock(): void
    {
        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
        ]);
    }

    /** Create a new draft version from this invoice, locking the current one. */
    public function createNewVersion(): self
    {
        if (! in_array($this->status, self::REVISABLE_STATUSES, true)) {
            throw new \LogicException("Cannot revise an invoice with status '{$this->status}'.");
        }

        $this->lock();

        return self::create([
            'service_request_id'  => $this->service_request_id,
            'work_order_id'       => $this->work_order_id,
            'invoice_number'      => $this->invoice_number,
            'status'              => self::STATUS_DRAFT,
            'version'             => $this->version + 1,
            'parent_version_id'   => $this->id,
            'customer_name'       => $this->customer_name,
            'customer_phone'      => $this->customer_phone,
            'vehicle_description' => $this->vehicle_description,
            'service_description' => $this->service_description,
            'service_location'    => $this->service_location,
            'line_items'          => $this->line_items,
            'subtotal'            => $this->subtotal,
            'tax_rate'            => $this->tax_rate,
            'tax_amount'          => $this->tax_amount,
            'total'               => $this->total,
            'due_date'            => $this->due_date,
            'payment_terms'       => $this->payment_terms,
            'notes'               => $this->notes,
            'issued_by'           => $this->issued_by,
            'company_snapshot'    => $this->company_snapshot,
        ]);
    }

    public function displayNumber(): string
    {
        return $this->version > 1
            ? $this->invoice_number . '-V' . $this->version
            : $this->invoice_number;
    }

    public function receipts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function paymentRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentRecord::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** Whether a transition to the given status is allowed from the current status. */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
