<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Vehicle;
use App\Models\ServiceType;
use App\Models\ServiceRequestStatusLog;
use App\Models\Setting;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $vehicle_id
 * @property string|null $vehicle_year
 * @property string|null $vehicle_make
 * @property string|null $vehicle_model
 * @property string|null $vehicle_color
 * @property int|null $service_type_id
 * @property numeric|null $quoted_price
 * @property string $status
 * @property string|null $location
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $location_token
 * @property \Illuminate\Support\Carbon|null $location_token_expires_at
 * @property \Illuminate\Support\Carbon|null $location_shared_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Estimate> $estimates
 * @property-read int|null $estimates_count
 * @property-read \App\Models\Estimate|null $latestEstimate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read ServiceType|null $serviceType
 * @property-read Vehicle|null $vehicle
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereLocationSharedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereLocationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereLocationTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereQuotedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereServiceTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereVehicleColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereVehicleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereVehicleMake($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereVehicleModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereVehicleYear($value)
 * @mixin \Eloquent
 */
class ServiceRequest extends Model
{
    /** All valid statuses. */
    public const STATUSES = [
        'new', 'dispatched', 'en_route', 'on_scene', 'completed', 'cancelled',
    ];

    /** Terminal statuses that cannot transition further. */
    public const TERMINAL_STATUSES = ['completed', 'cancelled'];

    /** Allowed forward transitions (status => next status). Cancel from any non-terminal. */
    public const TRANSITIONS = [
        'new'        => 'dispatched',
        'dispatched' => 'en_route',
        'en_route'   => 'on_scene',
        'on_scene'   => 'completed',
    ];

    /** Human-readable labels. */
    public const STATUS_LABELS = [
        'new'        => 'New',
        'dispatched' => 'Dispatched',
        'en_route'   => 'En Route',
        'on_scene'   => 'On Scene',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
    ];

    protected $fillable = [
        'customer_id',
        'vehicle_id',
        'vehicle_year',
        'vehicle_make',
        'vehicle_model',
        'vehicle_color',
        'service_type_id',
        'quoted_price',
        'status',
        'location',
        'latitude',
        'longitude',
        'location_token',
        'location_token_expires_at',
        'location_shared_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude'                 => 'decimal:7',
            'longitude'                => 'decimal:7',
            'quoted_price'             => 'decimal:2',
            'location_token_expires_at' => 'datetime',
            'location_shared_at'       => 'datetime',
        ];
    }

    /**
     * Generate a unique location-sharing token (valid for 4 hours).
     */
    public function generateLocationToken(): string
    {
        $token = \Illuminate\Support\Str::random(40);
        $hours = (int) Setting::getValue('location_link_expiry_hours', 4);

        $this->update([
            'location_token'            => $token,
            'location_token_expires_at' => now()->addHours($hours ?: 4),
            'location_shared_at'        => null,
        ]);

        return $token;
    }

    /**
     * Whether the location token is still valid.
     */
    public function isLocationTokenValid(): bool
    {
        return $this->location_token
            && $this->location_token_expires_at
            && $this->location_token_expires_at->isFuture()
            && is_null($this->location_shared_at);
    }

    /**
     * Build the public URL for this location token.
     *
     * Uses LOCATION_BASE_URL (pointing to the standalone hosting script)
     * so the link works from a phone over HTTPS.
     */
    public function locationShareUrl(): ?string
    {
        if (! $this->location_token) {
            return null;
        }

        $base = Setting::getValue('location_base_url', config('services.location.base_url'));

        if ($base) {
            // Normalise the base URL: fix common typos like https:// → https://
            $base = preg_replace('#^(https?):/{0,2}#i', '$1://', $base);

            // Standalone hosting script uses ?t= query param
            return rtrim($base, '/') . '?t=' . $this->location_token;
        }

        // Fallback to Laravel route (local dev)
        return url('/locate/' . $this->location_token);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ServicePhoto::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ServiceSignature::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class)->orderByDesc('logged_at');
    }

    public function paymentRecords(): HasMany
    {
        return $this->hasMany(PaymentRecord::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    public function latestEstimate()
    {
        return $this->hasOne(Estimate::class)->latestOfMany();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ServiceRequestStatusLog::class)->orderBy('created_at');
    }

    /** Get the next forward status, or null if terminal. */
    public function nextStatus(): ?string
    {
        return self::TRANSITIONS[$this->status] ?? null;
    }

    /** Whether a transition to the given status is allowed. */
    public function canTransitionTo(string $status): bool
    {
        // Forward transition
        if (($this->nextStatus() ?? '') === $status) {
            return true;
        }

        // Cancel from any non-terminal status
        if ($status === 'cancelled' && ! in_array($this->status, self::TERMINAL_STATUSES, true)) {
            return true;
        }

        return false;
    }

    /** Human-readable label for the current status. */
    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    /** Total payments collected for this SR. */
    public function totalPayments(): float
    {
        return (float) $this->paymentRecords->sum('amount');
    }

    /** Payment status: paid, partial, or unpaid. */
    public function paymentStatus(): string
    {
        $paid = $this->totalPayments();
        if ($paid <= 0) {
            return 'unpaid';
        }
        $expected = (float) ($this->latestEstimate?->total ?? $this->quoted_price ?? 0);
        if ($expected > 0 && $paid >= $expected) {
            return 'paid';
        }
        return 'partial';
    }
}
