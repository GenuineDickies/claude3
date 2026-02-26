<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? ucfirst($this->method);
    }
}
