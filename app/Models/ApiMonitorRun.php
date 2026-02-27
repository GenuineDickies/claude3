<?php

namespace App\Models;

use App\Models\ApiMonitorEndpoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiMonitorRun extends Model
{
    protected $fillable = [
        'endpoint_id',
        'status_code',
        'response_time_ms',
        'is_success',
        'status',
        'error_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_success' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiMonitorEndpoint::class, 'endpoint_id');
    }
}
