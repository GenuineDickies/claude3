<?php

namespace App\Models;

use App\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An individual service offered within a category (e.g. "Spare Tire Swap").
 *
 * @property int $id
 * @property int $catalog_category_id
 * @property string $name
 * @property string|null $description
 * @property numeric $base_cost
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
 * @mixin \Eloquent
 */
class CatalogItem extends Model
{
    protected $fillable = [
        'catalog_category_id',
        'name',
        'description',
        'base_cost',
        'unit',
        'pricing_type',
        'is_active',
        'sort_order',
        'revenue_account_id',
        'cogs_account_id',
        'core_required',
        'core_amount',
        'taxable',
    ];

    protected function casts(): array
    {
        return [
            'base_cost'     => 'decimal:2',
            'core_amount'   => 'decimal:2',
            'is_active'     => 'boolean',
            'core_required' => 'boolean',
            'taxable'       => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'catalog_category_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
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
