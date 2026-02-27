<?php

namespace App\Models;

use App\Models\CatalogItem;
use App\Models\ChangeOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $change_order_id
 * @property int|null $catalog_item_id
 * @property string $description
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read CatalogItem|null $catalogItem
 * @property-read ChangeOrder $changeOrder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereCatalogItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereChangeOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangeOrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ChangeOrderItem extends Model
{
    protected $fillable = [
        'change_order_id',
        'catalog_item_id',
        'description',
        'quantity',
        'unit_price',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
