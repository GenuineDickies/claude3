<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string $old_status
 * @property string $new_status
 * @property int|null $changed_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereChangedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereNewStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereOldStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequestStatusLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ServiceRequestStatusLog extends Model
{
    protected $fillable = [
        'service_request_id',
        'old_status',
        'new_status',
        'changed_by',
        'notes',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
