<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_name',
        'description',
        'requires_mobile_phone',
        'requires_sms_consent',
    ];

    protected function casts(): array
    {
        return [
            'requires_mobile_phone' => 'boolean',
            'requires_sms_consent' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    public function isAdministrator(): bool
    {
        return strcasecmp($this->role_name, 'Administrator') === 0;
    }

    public function isTechnician(): bool
    {
        return strcasecmp($this->role_name, 'Technician') === 0;
    }

    public function requiresMobilePhone(): bool
    {
        return (bool) $this->requires_mobile_phone;
    }

    public function requiresSmsConsent(): bool
    {
        return (bool) $this->requires_sms_consent;
    }
}