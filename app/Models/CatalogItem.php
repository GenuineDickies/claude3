<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $catalog_category_id
 * @property string $name
 * @property string|null $sku
 * @property string|null $description
 * @property numeric $unit_price
 * @property string $unit
 * @property string $pricing_type
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CatalogCategory $category
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereCatalogCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem wherePricingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CatalogItem extends Model
{
    protected $fillable = [
        'catalog_category_id',
        'name',
        'sku',
        'description',
        'unit_price',
        'unit',
        'pricing_type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'catalog_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isVariable(): bool
    {
        return $this->pricing_type === 'variable';
    }

    public function isFixed(): bool
    {
        return $this->pricing_type === 'fixed';
    }

    public static function pricingTypes(): array
    {
        return [
            'fixed' => 'Fixed',
            'variable' => 'Variable',
        ];
    }

    public static function units(): array
    {
        return [
            'each' => 'Each',
            'mile' => 'Mile',
            'hour' => 'Hour',
            'gallon' => 'Gallon',
        ];
    }
}
