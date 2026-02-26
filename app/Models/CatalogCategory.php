<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $type
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CatalogCategory extends Model
{
    protected $fillable = [
        'name',
        'type',
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

    public static function types(): array
    {
        return [
            'part' => 'Part',
            'service' => 'Service',
        ];
    }
}
