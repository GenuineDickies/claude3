<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const CATEGORIES = [
        'fuel'           => 'Fuel',
        'supplies'       => 'Supplies',
        'parts'          => 'Parts',
        'vehicle_repair' => 'Vehicle Repair',
        'insurance'      => 'Insurance',
        'licensing'      => 'Licensing',
        'tools'          => 'Tools',
        'marketing'      => 'Marketing',
        'office'         => 'Office',
        'other'          => 'Other',
    ];

    public const PAYMENT_METHODS = [
        'cash'     => 'Cash',
        'card'     => 'Credit/Debit Card',
        'check'    => 'Check',
        'transfer' => 'Bank Transfer',
    ];

    protected $fillable = [
        'expense_number',
        'date',
        'vendor',
        'description',
        'category',
        'amount',
        'payment_method',
        'reference_number',
        'receipt_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'   => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Generate the next expense number in format EXP-YYYYMMDD-XXXX.
     */
    public static function generateExpenseNumber(): string
    {
        $prefix = 'EXP-' . now()->format('Ymd') . '-';

        $latest = static::where('expense_number', 'like', $prefix . '%')
            ->orderByDesc('expense_number')
            ->value('expense_number');

        $seq = 1;
        if ($latest) {
            $seq = ((int) substr($latest, -4)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method ?? '');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
