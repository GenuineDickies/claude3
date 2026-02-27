<?php

namespace App\Models;

use App\Models\ChangeOrderItem;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $work_order_id
 * @property string $change_type
 * @property string $description
 * @property numeric $price_impact
 * @property bool $requires_customer_approval
 * @property string $approval_status
 * @property string|null $approval_method
 * @property string|null $approval_token
 * @property \Illuminate\Support\Carbon|null $approval_token_expires_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approved_by_name
 * @property array<array-key, mixed>|null $approval_device_info
 * @property string|null $signature_data
 * @property string|null $technician_notes
 * @property string|null $rejection_resolution
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ChangeOrderItem> $items
 * @property-read int|null $items_count
 * @property-read WorkOrder $workOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereApprovalDeviceInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereApprovalMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereApprovalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereApprovalToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereApprovalTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereApprovedByName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereChangeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder wherePriceImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereRejectionResolution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereRequiresCustomerApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereSignatureData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereTechnicianNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrder whereWorkOrderId($value)
 * @mixin \Eloquent
 */
class ChangeOrder extends Model
{
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_CANCELLED = 'cancelled';
    public const APPROVAL_NOT_REQUIRED = 'not_required';

    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING,
        self::APPROVAL_APPROVED,
        self::APPROVAL_REJECTED,
        self::APPROVAL_CANCELLED,
        self::APPROVAL_NOT_REQUIRED,
    ];

    protected $fillable = [
        'work_order_id',
        'change_type',
        'description',
        'price_impact',
        'requires_customer_approval',
        'approval_status',
        'approval_method',
        'approval_token',
        'approval_token_expires_at',
        'approved_at',
        'approved_by_name',
        'approval_device_info',
        'signature_data',
        'technician_notes',
        'rejection_resolution',
    ];

    protected function casts(): array
    {
        return [
            'price_impact' => 'decimal:2',
            'requires_customer_approval' => 'boolean',
            'approval_token_expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'approval_device_info' => 'array',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChangeOrderItem::class);
    }

    public function isApprovalOpen(): bool
    {
        if (! $this->requires_customer_approval) {
            return false;
        }

        if ($this->approval_status !== self::APPROVAL_PENDING) {
            return false;
        }

        return $this->approval_token_expires_at === null || $this->approval_token_expires_at->isFuture();
    }
}
