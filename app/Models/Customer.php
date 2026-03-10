<?php

namespace App\Models;

use App\Events\CustomerOptedIn;
use App\Events\CustomerOptedOut;
use App\Models\Correspondence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $sms_consent_at
 * @property \Illuminate\Support\Carbon|null $sms_opt_out_at
 * @property array<array-key, mixed>|null $notification_preferences
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Correspondence> $correspondences
 * @property-read int|null $correspondences_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceRequest> $serviceRequests
 * @property-read int|null $service_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vehicle> $vehicles
 * @property-read int|null $vehicles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereNotificationPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereSmsConsentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereSmsOptOutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Customer extends Model
{
    public const DEFAULT_NOTIFICATION_PREFERENCES = [
        'status_updates'     => true,
        'location_requests'  => true,
        'signature_requests' => true,
        'marketing'          => true,
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'is_active',
        'sms_consent_at',
        'sms_opt_out_at',
        'notification_preferences',
    ];

    protected function casts(): array
    {
        return [
            'is_active'                => 'boolean',
            'sms_consent_at'           => 'datetime',
            'sms_opt_out_at'           => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Check if the customer wants to receive a specific notification type.
     * Defaults to true for unknown or unset types.
     */
    public function wantsNotification(string $type): bool
    {
        $prefs = $this->notification_preferences ?? self::DEFAULT_NOTIFICATION_PREFERENCES;

        return $prefs[$type] ?? true;
    }

    /**
     * Whether the customer has active SMS consent (opted-in and not opted-out).
     */
    public function hasSmsConsent(): bool
    {
        if (is_null($this->sms_consent_at)) {
            return false;
        }

        // If they opted out after opting in, consent is revoked
        if ($this->sms_opt_out_at && $this->sms_opt_out_at->isAfter($this->sms_consent_at)) {
            return false;
        }

        return true;
    }

    /**
     * Record SMS opt-in consent.
     */
    public function grantSmsConsent(): void
    {
        $this->update([
            'sms_consent_at' => now(),
            'sms_opt_out_at' => null,
        ]);

        CustomerOptedIn::dispatch($this);
    }

    /**
     * Record SMS opt-out.
     */
    public function revokeSmsConsent(): void
    {
        $this->update([
            'sms_opt_out_at' => now(),
        ]);

        CustomerOptedOut::dispatch($this);
    }

    /**
     * Normalize a phone number to digits-only format.
     * Strips all non-digit characters and removes leading 1 for 11-digit US numbers.
     *
     * @param string $phone
     * @return string
     */
    public static function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/\D/', '', $phone);
        // Remove leading '1' for 11-digit US numbers (e.g., 15551234567 -> 5551234567)
        if (strlen($normalized) === 11 && substr($normalized, 0, 1) === '1') {
            return substr($normalized, 1);
        }
        return $normalized;
    }

    /**
     * Strip phone to digits-only before saving.
     * Ensures consistent storage regardless of input format.
     */
    public function setPhoneAttribute(string $value): void
    {
        $this->attributes['phone'] = self::normalizePhone($value);
    }

    /**
     * Match phones across normalized, country-code, and legacy formatted values.
     */
    public function scopeWherePhoneMatches(Builder $query, string $phone): Builder
    {
        $normalized = self::normalizePhone($phone);

        if ($normalized === '') {
            return $query->whereRaw('1 = 0');
        }

        $candidates = [$normalized];

        if (strlen($normalized) === 10) {
            $candidates[] = '1' . $normalized;
        } elseif (strlen($normalized) === 11 && str_starts_with($normalized, '1')) {
            $candidates[] = substr($normalized, 1);
        }

        $candidates = array_values(array_unique($candidates));
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));
        $normalizedPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), '(', ''), ')', ''), '-', ''), ' ', ''), '.', '')";

        return $query->where(function (Builder $phoneQuery) use ($candidates, $normalizedPhoneSql, $placeholders) {
            $phoneQuery->whereIn('phone', $candidates)
                ->orWhereRaw($normalizedPhoneSql . " IN ($placeholders)", $candidates);
        });
    }

    public static function findActiveByPhone(string $phone): ?self
    {
        $normalized = static::normalizePhone($phone);

        if ($normalized === '') {
            return null;
        }

        $customer = static::query()
            ->wherePhoneMatches($phone)
            ->where('is_active', true)
            ->first();

        if ($customer) {
            return $customer;
        }

        // Fallback for legacy rows with unexpected formatting that SQL-side
        // replacement does not normalize cleanly.
        return static::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (self $activeCustomer) => static::normalizePhone((string) $activeCustomer->phone) === $normalized);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function correspondences(): HasMany
    {
        return $this->hasMany(Correspondence::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
