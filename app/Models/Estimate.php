<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string|null $state_code
 * @property numeric $tax_rate
 * @property numeric $subtotal
 * @property numeric $tax_amount
 * @property numeric $total
 * @property string|null $notes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EstimateItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereStateCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Estimate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Estimate extends Model
{
    protected $fillable = [
        'service_request_id',
        'state_code',
        'tax_rate',
        'subtotal',
        'tax_amount',
        'total',
        'notes',
        'status',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class)->orderBy('sort_order');
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
