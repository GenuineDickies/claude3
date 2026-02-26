<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
