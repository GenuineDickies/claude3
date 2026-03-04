<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'account_number',
        'payment_terms',
        'default_expense_account_id',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function defaultExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_expense_account_id');
    }

    // ── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ────────────────────────────────────────

    public function fullAddress(): ?string
    {
        $parts = array_filter([$this->address, $this->city, $this->state, $this->zip]);
        return $parts ? implode(', ', $parts) : null;
    }
}
