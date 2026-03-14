<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\TechnicianProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $sms_consent_at
 * @property array<array-key, mixed>|null $sms_consent_meta
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read TechnicianProfile|null $technicianProfile
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'sms_consent_at' => 'datetime',
            'sms_consent_meta' => 'array',
            'password' => 'hashed',
        ];
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $normalized = preg_replace('/\D/', '', $phone);

        if ($normalized === '') {
            return null;
        }

        if (strlen($normalized) === 11 && str_starts_with($normalized, '1')) {
            return substr($normalized, 1);
        }

        return $normalized;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = self::normalizePhone($value);
    }

    public function technicianProfile(): HasOne
    {
        return $this->hasOne(TechnicianProfile::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdministrator(): bool
    {
        return $this->roles->contains(fn (Role $role): bool => $role->isAdministrator());
    }

    public function requiresMobilePhone(): bool
    {
        return $this->roles->contains(fn (Role $role): bool => $role->requiresMobilePhone());
    }

    public function requiresSmsConsent(): bool
    {
        return $this->roles->contains(fn (Role $role): bool => $role->requiresSmsConsent());
    }

    public function hasSmsConsent(): bool
    {
        if (array_key_exists('sms_consent_at', $this->attributes)) {
            return $this->attributes['sms_consent_at'] !== null;
        }

        if (! $this->exists) {
            return false;
        }

        return static::query()
            ->whereKey($this->getKey())
            ->whereNotNull('sms_consent_at')
            ->exists();
    }

    public function grantSmsConsent(array $meta = []): void
    {
        $this->forceFill([
            'sms_consent_at' => now(),
            'sms_consent_meta' => $meta,
        ])->save();
    }
}
