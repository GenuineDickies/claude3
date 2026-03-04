<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A service category groups related services (e.g. "Tire Services", "Battery Services").
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CatalogItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory query()
 * @mixin \Eloquent
 */
class CatalogCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

}
