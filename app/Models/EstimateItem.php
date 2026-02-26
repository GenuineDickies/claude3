<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $estimate_id
 * @property int|null $catalog_item_id
 * @property string $name
 * @property string|null $description
 * @property numeric $unit_price
 * @property numeric $quantity
 * @property string $unit
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CatalogItem|null $catalogItem
 * @property-read \App\Models\Estimate $estimate
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereCatalogItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereEstimateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstimateItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EstimateItem extends Model
{
    protected $fillable = [
        'estimate_id',
        'catalog_item_id',
        'name',
        'description',
        'unit_price',
        'quantity',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function lineTotal(): float
    {
        return round($this->unit_price * $this->quantity, 2);
    }
}
