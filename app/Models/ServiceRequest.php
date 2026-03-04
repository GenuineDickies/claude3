<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Correspondence;
use App\Models\Vehicle;
use App\Models\CatalogItem;
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
 * @property int|null $catalog_item_id
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
 * @property-read CatalogItem|null $catalogItem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Correspondence> $correspondences
 * @property-read int|null $correspondences_count
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Estimate> $estimates
 * @property-read int|null $estimates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read \App\Models\Estimate|null $latestEstimate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PaymentRecord> $paymentRecords
 * @property-read int|null $payment_records_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServicePhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Receipt> $receipts
 * @property-read int|null $receipts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceLog> $serviceLogs
 * @property-read int|null $service_logs_count
 * @property-read CatalogItem|null $serviceType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ServiceSignature> $signatures
 * @property-read int|null $signatures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ServiceRequestStatusLog> $statusLogs
 * @property-read int|null $status_logs_count
 * @property-read Vehicle|null $vehicle
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Warranty> $warranties
 * @property-read int|null $warranties_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkOrder> $workOrders
 * @property-read int|null $work_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceRequest whereCatalogItemId($value)
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
        'catalog_item_id',
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

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * @deprecated Use catalogItem() instead. Kept for backward compatibility.
     */
    public function serviceType(): BelongsTo
    {
        return $this->catalogItem();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function correspondences(): HasMany
    {
        return $this->hasMany(Correspondence::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
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

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
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

        // Prefer the latest active invoice total (the authoritative billed amount),
        // then fall back to work order total (includes change orders), then estimate/quoted_price.
        $invoice = $this->invoices()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->latest()
            ->first();

        $expected = (float) ($invoice?->total
            ?? $this->workOrders()->latest()->value('total')
            ?? $this->latestEstimate?->total
            ?? $this->quoted_price
            ?? 0);

        if ($expected > 0 && $paid >= $expected) {
            return 'paid';
        }
        return 'partial';
    }
}
