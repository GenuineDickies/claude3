<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string|null $estimate_number
 * @property string|null $state_code
 * @property numeric $tax_rate
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $total
 * @property numeric|null $approved_total
 * @property string|null $notes
 * @property string $status
 * @property int $version
 * @property bool $is_locked
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property int|null $parent_version_id
 * @property string|null $approval_token
 * @property \Illuminate\Support\Carbon|null $approval_token_expires_at
 * @property string|null $signature_data
 * @property string|null $signer_name
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approval_ip_address
 * @property string|null $approval_user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, Estimate> $childVersions
 * @property-read int|null $child_versions_count
 * @property-read Collection<int, \App\Models\EstimateItem> $items
 * @property-read int|null $items_count
 * @property-read Estimate|null $parentVersion
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereApprovalIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereApprovalToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereApprovalTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereApprovalUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereApprovedTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereEstimateNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereLockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereParentVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereSignatureData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereSignerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereStateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereVersion($value)
 * @mixin \Eloquent
 */
class Estimate extends Model
{
    /** Statuses from which a new revision may be created. */
    public const REVISABLE_STATUSES = ['sent', 'declined', 'pending_approval'];

    protected $attributes = [
        'version'   => 1,
        'is_locked' => false,
    ];

    protected $fillable = [
        'service_request_id',
        'estimate_number',
        'state_code',
        'tax_rate',
        'subtotal',
        'tax_amount',
        'total',
        'approved_total',
        'notes',
        'status',
        'version',
        'is_locked',
        'locked_at',
        'parent_version_id',
        'approval_token',
        'approval_token_expires_at',
        'signature_data',
        'signer_name',
        'approved_at',
        'approval_ip_address',
        'approval_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'approved_total' => 'decimal:2',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
            'approval_token_expires_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'pending_approval' => 'Pending Approval',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
        ];
    }

    /** Check if this estimate's total exceeds the configured signature threshold. */
    public function requiresApproval(): bool
    {
        $mode = Setting::getValue('estimate_approval_mode', 'none');

        if ($mode === 'all') {
            return true;
        }

        if ($mode === 'threshold') {
            $threshold = Setting::getValue('estimate_signature_threshold');
            if ($threshold !== null && $threshold !== '') {
                return (float) $this->total > (float) $threshold;
            }
        }

        return false;
    }

    /** Check if this estimate has been approved via customer signature. */
    public function isApproved(): bool
    {
        return $this->status === 'accepted' && $this->approved_at !== null;
    }

    /** Check if the approval token is still valid and the estimate is awaiting approval. */
    public function isApprovalOpen(): bool
    {
        return $this->status === 'pending_approval'
            && $this->approval_token !== null
            && ($this->approval_token_expires_at === null || $this->approval_token_expires_at->isFuture());
    }

    /** Generate a secure approval token and set expiry (7 days). */
    public function generateApprovalToken(): string
    {
        $token = Str::random(48);

        $this->update([
            'approval_token' => $token,
            'approval_token_expires_at' => now()->addDays(7),
            'status' => 'pending_approval',
        ]);

        return $token;
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class)->orderBy('sort_order');
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    public function childVersions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_version_id');
    }

    /** Get all versions sharing the same estimate_number, ordered by version. */
    public function allVersions(): Collection
    {
        if (!$this->estimate_number) {
            return new Collection([$this]);
        }

        return self::where('estimate_number', $this->estimate_number)
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

    /** Create a new draft version from this estimate, locking the current one. */
    public function createNewVersion(): self
    {
        if (! in_array($this->status, self::REVISABLE_STATUSES, true)) {
            throw new \LogicException("Cannot revise an estimate with status '{$this->status}'.");
        }

        $this->loadMissing('items');
        $this->lock();

        $newEstimate = self::create([
            'service_request_id' => $this->service_request_id,
            'estimate_number'    => $this->estimate_number,
            'state_code'         => $this->state_code,
            'tax_rate'           => $this->tax_rate,
            'subtotal'           => $this->subtotal,
            'tax_amount'         => $this->tax_amount,
            'total'              => $this->total,
            'notes'              => $this->notes,
            'status'             => 'draft',
            'version'            => $this->version + 1,
            'parent_version_id'  => $this->id,
        ]);

        foreach ($this->items as $item) {
            EstimateItem::create([
                'estimate_id'     => $newEstimate->id,
                'catalog_item_id' => $item->catalog_item_id,
                'name'            => $item->name,
                'description'     => $item->description,
                'unit_price'      => $item->unit_price,
                'quantity'        => $item->quantity,
                'unit'            => $item->unit,
                'sort_order'      => $item->sort_order,
            ]);
        }

        return $newEstimate;
    }

    public static function generateEstimateNumber(): string
    {
        $prefix = 'EST-' . now()->format('Ymd') . '-';

        $latest = static::where('estimate_number', 'like', $prefix . '%')
            ->orderByDesc('estimate_number')
            ->value('estimate_number');

        $seq = 1;
        if ($latest) {
            $seq = ((int) substr($latest, -4)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function displayNumber(): string
    {
        $num = $this->estimate_number ?? '#' . $this->id;

        return $this->version > 1 ? $num . '-V' . $this->version : $num;
    }

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum(
            \Illuminate\Support\Facades\DB::raw('unit_price * quantity')
        );

        $taxAmount = round($subtotal * ($this->tax_rate / 100), 2);

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);
    }
}
