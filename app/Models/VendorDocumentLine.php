<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocumentLine extends Model
{
    public const TYPE_PART        = 'part';
    public const TYPE_SERVICE     = 'service';
    public const TYPE_EXPENSE     = 'expense';
    public const TYPE_CORE_CHARGE = 'core_charge';
    public const TYPE_SHIPPING    = 'shipping';
    public const TYPE_TAX         = 'tax';

    public const TYPES = [
        self::TYPE_PART        => 'Part',
        self::TYPE_SERVICE     => 'Service',
        self::TYPE_EXPENSE     => 'Expense',
        self::TYPE_CORE_CHARGE => 'Core Charge',
        self::TYPE_SHIPPING    => 'Shipping',
        self::TYPE_TAX         => 'Tax',
    ];

    protected $fillable = [
        'vendor_document_id',
        'line_type',
        'description',
        'part_id',
        'qty',
        'unit_cost',
        'line_total',
        'core_amount',
        'taxable',
        'expense_account_id',
        'cogs_account_id',
        'job_link_id',
    ];

    protected function casts(): array
    {
        return [
            'qty'         => 'decimal:3',
            'unit_cost'   => 'decimal:2',
            'line_total'  => 'decimal:2',
            'core_amount' => 'decimal:2',
            'taxable'     => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function vendorDocument(): BelongsTo
    {
        return $this->belongsTo(VendorDocument::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'part_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    // ── Helpers ────────────────────────────────────────

    public function typeLabel(): string
    {
        return self::TYPES[$this->line_type] ?? ucfirst($this->line_type);
    }

    public function hasCoreCharge(): bool
    {
        return (float) $this->core_amount > 0;
    }
}
