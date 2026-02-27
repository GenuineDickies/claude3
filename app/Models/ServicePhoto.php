<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string $file_path
 * @property string|null $caption
 * @property \Illuminate\Support\Carbon|null $taken_at
 * @property string $type
 * @property int|null $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @property-read \App\Models\User|null $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereCaption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereTakenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServicePhoto whereUploadedBy($value)
 * @mixin \Eloquent
 */
class ServicePhoto extends Model
{
    protected $fillable = [
        'service_request_id',
        'file_path',
        'caption',
        'taken_at',
        'type',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
