<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $service_request_id
 * @property string $channel
 * @property string $direction
 * @property string|null $subject
 * @property string|null $body
 * @property int|null $logged_by
 * @property \Illuminate\Support\Carbon $logged_at
 * @property int|null $duration_minutes
 * @property string|null $outcome
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read string $channel_label
 * @property-read \App\Models\User|null $logger
 * @property-read \App\Models\ServiceRequest|null $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence byChannel(string $channel)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence chronological()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence forCustomer(int $customerId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence forServiceRequest(int $serviceRequestId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence reverseChronological()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereLoggedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereLoggedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Correspondence whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Correspondence extends Model
{
    use HasFactory;

    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_IN_PERSON = 'in_person';
    public const CHANNEL_OTHER = 'other';

    public const CHANNELS = [
        self::CHANNEL_SMS,
        self::CHANNEL_PHONE,
        self::CHANNEL_EMAIL,
        self::CHANNEL_IN_PERSON,
        self::CHANNEL_OTHER,
    ];

    public const CHANNEL_LABELS = [
        self::CHANNEL_SMS => 'SMS',
        self::CHANNEL_PHONE => 'Phone Call',
        self::CHANNEL_EMAIL => 'Email',
        self::CHANNEL_IN_PERSON => 'In Person',
        self::CHANNEL_OTHER => 'Other',
    ];

    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    public const DIRECTIONS = [
        self::DIRECTION_INBOUND,
        self::DIRECTION_OUTBOUND,
    ];

    protected $fillable = [
        'customer_id',
        'service_request_id',
        'channel',
        'direction',
        'subject',
        'body',
        'logged_by',
        'logged_at',
        'duration_minutes',
        'outcome',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function getChannelLabelAttribute(): string
    {
        return self::CHANNEL_LABELS[$this->channel] ?? ucfirst(str_replace('_', ' ', $this->channel));
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForServiceRequest($query, int $serviceRequestId)
    {
        return $query->where('service_request_id', $serviceRequestId);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('logged_at', 'asc');
    }

    public function scopeReverseChronological($query)
    {
        return $query->orderBy('logged_at', 'desc');
    }
}
