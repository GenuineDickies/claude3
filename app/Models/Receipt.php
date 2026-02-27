<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_request_id
 * @property int|null $invoice_id
 * @property string $receipt_number
 * @property string $customer_name
 * @property string|null $customer_phone
 * @property string|null $vehicle_description
 * @property string|null $service_description
 * @property string|null $service_location
 * @property array<array-key, mixed> $line_items
 * @property numeric $subtotal
 * @property numeric $tax_rate
 * @property numeric $tax_amount
 * @property numeric $total
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property \Illuminate\Support\Carbon|null $payment_date
 * @property string|null $notes
 * @property int|null $issued_by
 * @property array<array-key, mixed> $company_snapshot
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Invoice|null $invoice
 * @property-read \App\Models\User|null $issuedBy
 * @property-read \App\Models\PaymentRecord|null $paymentRecord
 * @property-read \App\Models\ServiceRequest $serviceRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCompanySnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCustomerPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereIssuedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereLineItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt wherePaymentReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReceiptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereServiceDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereServiceLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereServiceRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereVehicleDescription($value)
 * @mixin \Eloquent
 */
class Receipt extends Model
{
    protected $fillable = [
        'service_request_id',
        'invoice_id',
        'payment_record_id',
        'receipt_number',
        'customer_name',
        'customer_phone',
        'vehicle_description',
        'service_description',
        'service_location',
        'line_items',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'payment_method',
        'payment_reference',
        'payment_date',
        'notes',
        'issued_by',
        'company_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'line_items'       => 'array',
            'company_snapshot' => 'array',
            'subtotal'         => 'decimal:2',
            'tax_rate'         => 'decimal:4',
            'tax_amount'       => 'decimal:2',
            'total'            => 'decimal:2',
            'payment_date'     => 'date',
        ];
    }

    /**
     * Generate the next receipt number in format R-YYYYMMDD-XXXX.
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'R-' . now()->format('Ymd') . '-';

        $latest = static::where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $seq = 1;
        if ($latest) {
            $seq = ((int) substr($latest, -4)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentRecord(): BelongsTo
    {
        return $this->belongsTo(PaymentRecord::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
