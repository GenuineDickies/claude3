<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int|null $invoice_id
 * @property string $method
 * @property numeric $amount
 * @property string|null $reference
 * @property \Illuminate\Support\Carbon $collected_at
 * @property int|null $collected_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $collector
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\Receipt|null $receipt
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereCollectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereCollectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PaymentRecord extends Model
{
    public const METHODS = ['cash', 'card', 'venmo', 'zelle', 'check', 'other'];

    public const METHOD_LABELS = [
        'cash'  => 'Cash',
        'card'  => 'Credit/Debit Card',
        'venmo' => 'Venmo',
        'zelle' => 'Zelle',
        'check' => 'Check',
        'other' => 'Other',
    ];

    protected $fillable = [
        'service_request_id',
        'invoice_id',
        'method',
        'amount',
        'reference',
        'collected_at',
        'collected_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'collected_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? ucfirst($this->method);
    }
}
