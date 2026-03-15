<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $year
 * @property string $make
 * @property string $model
 * @property string|null $color
 * @property string|null $license_plate
 * @property string|null $vin
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceRequest> $serviceRequests
 * @property-read int|null $service_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereMake($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vehicle whereYear($value)
 * @mixin \Eloquent
 */
class Vehicle extends Model
{
    protected $fillable = [
        'customer_id',
        'year',
        'make',
        'model',
        'color',
        'license_plate',
        'vin',
    ];

    public static function normalizeLicensePlate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));

        return $normalized === '' ? null : $normalized;
    }

    public static function normalizeVin(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/\s+/', '', $normalized ?? '');

        return $normalized === '' ? null : $normalized;
    }

    public function setLicensePlateAttribute(?string $value): void
    {
        $this->attributes['license_plate'] = self::normalizeLicensePlate($value);
    }

    public function setVinAttribute(?string $value): void
    {
        $this->attributes['vin'] = self::normalizeVin($value);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function displayName(): string
    {
        return trim(implode(' ', array_filter([
            $this->color,
            $this->year,
            $this->make,
            $this->model,
        ])));
    }
}
