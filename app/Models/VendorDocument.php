<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VendorDocument extends Model
{
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_INVOICE = 'invoice';

    public const TYPES = [
        self::TYPE_RECEIPT => 'Receipt',
        self::TYPE_INVOICE => 'Invoice',
    ];

    public const STATUS_DRAFT  = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID   = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT  => 'Draft',
        self::STATUS_POSTED => 'Posted',
        self::STATUS_VOID   => 'Void',
    ];

    public const PAYMENT_METHODS = [
        'cash'   => 'Cash',
        'check'  => 'Check',
        'card'   => 'Credit/Debit Card',
        'ach'    => 'ACH/Bank Transfer',
        'other'  => 'Other',
    ];

    protected $fillable = [
        'vendor_id',
        'document_date',
        'document_type',
        'vendor_document_number',
        'subtotal',
        'tax_total',
        'shipping_total',
        'total',
        'payment_method',
        'is_paid',
        'paid_at',
        'job_link_type',
        'job_link_id',
        'status',
        'posted_at',
        'posted_by',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date'  => 'date',
            'subtotal'       => 'decimal:2',
            'tax_total'      => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'total'          => 'decimal:2',
            'is_paid'        => 'boolean',
            'paid_at'        => 'datetime',
            'posted_at'      => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorDocumentLine::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VendorDocumentAttachment::class);
    }

    public function jobLink(): MorphTo
    {
        return $this->morphTo('job_link');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function accountingLinks(): HasMany
    {
        return $this->hasMany(DocumentAccountingLink::class, 'document_id')
            ->where('document_type', self::class);
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    // ── Helpers ────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function isReceipt(): bool
    {
        return $this->document_type === self::TYPE_RECEIPT;
    }

    public function isInvoice(): bool
    {
        return $this->document_type === self::TYPE_INVOICE;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->document_type] ?? ucfirst($this->document_type);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method ?? '—');
    }

    /**
     * Recalculate totals from line items.
     */
    public function recalculate(): void
    {
        $lines = $this->lines()->get();

        $subtotal = $lines->where('line_type', '!=', 'tax')
                          ->where('line_type', '!=', 'shipping')
                          ->sum('line_total');

        $taxTotal      = $lines->where('line_type', 'tax')->sum('line_total');
        $shippingTotal = $lines->where('line_type', 'shipping')->sum('line_total');

        $this->update([
            'subtotal'       => $subtotal,
            'tax_total'      => $taxTotal,
            'shipping_total' => $shippingTotal,
            'total'          => $subtotal + $taxTotal + $shippingTotal,
        ]);
    }

    /**
     * Total core charges across all lines.
     */
    public function totalCoreCharges(): float
    {
        return (float) $this->lines()->sum('core_amount');
    }
}
