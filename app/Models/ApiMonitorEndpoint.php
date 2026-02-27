<?php

namespace App\Models;

use App\Models\ApiMonitorRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiMonitorEndpoint extends Model
{
    protected $fillable = [
        'name',
        'url',
        'method',
        'headers',
        'expected_status_code',
        'check_interval_minutes',
        'is_active',
        'last_checked_at',
        'last_status',
        'last_response_time_ms',
        'last_error',
        'consecutive_failures',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ApiMonitorRun::class, 'endpoint_id');
    }
}
