<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $work_order_id
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
 * @property-read \App\Models\WorkOrder $workOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereCatalogItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrderItem whereWorkOrderId($value)
 * @mixin \Eloquent
 */
class WorkOrderItem extends Model
{
    protected $fillable = [
        'work_order_id',
        'catalog_item_id',
        'name',
        'description',
        'unit_price',
        'quantity',
        'unit',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity'   => 'decimal:2',
        ];
    }

    public function lineTotal(): float
    {
        return round($this->unit_price * $this->quantity, 2);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
