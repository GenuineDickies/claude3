<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string $part_name
 * @property string|null $part_number
 * @property string|null $vendor_name
 * @property string|null $vendor_phone
 * @property string|null $vendor_invoice_number
 * @property \Illuminate\Support\Carbon $install_date
 * @property int $warranty_months
 * @property \Illuminate\Support\Carbon $warranty_expires_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Document> $documents
 * @property-read int|null $documents_count
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereInstallDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty wherePartName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty wherePartNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereVendorInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereVendorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereVendorPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereWarrantyExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warranty whereWarrantyMonths($value)
 * @mixin \Eloquent
 */
class Warranty extends Model
{
    protected $fillable = [
        'service_request_id',
        'part_name',
        'part_number',
        'vendor_name',
        'vendor_phone',
        'vendor_invoice_number',
        'install_date',
        'warranty_months',
        'warranty_expires_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'install_date'         => 'date',
            'warranty_expires_at'  => 'date',
            'warranty_months'      => 'integer',
        ];
    }

    /** Expiry status: active, expiring_soon (within 30 days), or expired. */
    public function expiryStatus(): string
    {
        $expires = $this->warranty_expires_at;

        if ($expires->isPast()) {
            return 'expired';
        }

        if ($expires->diffInDays(today()) <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public const EXPIRY_LABELS = [
        'active'        => 'Active',
        'expiring_soon' => 'Expiring Soon',
        'expired'       => 'Expired',
    ];

    public const EXPIRY_COLORS = [
        'active'        => 'green',
        'expiring_soon' => 'yellow',
        'expired'       => 'red',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
