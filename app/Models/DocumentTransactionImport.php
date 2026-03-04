<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTransactionImport extends Model
{
    protected $fillable = [
        'document_id',
        'transaction_date',
        'description',
        'amount',
        'type',
        'category',
        'vendor',
        'payment_method',
        'reference',
        'account_code',
        'raw_data',
        'status',
        'created_expense_id',
        'created_journal_entry_id',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount'           => 'decimal:2',
            'raw_data'         => 'array',
            'reviewed_at'      => 'datetime',
        ];
    }

    public const STATUS_DRAFT    = 'draft';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = ['draft', 'accepted', 'rejected'];

    public const TYPE_EXPENSE  = 'expense';
    public const TYPE_INCOME   = 'income';
    public const TYPE_TRANSFER = 'transfer';

    public const TYPES = ['expense', 'income', 'transfer'];

    // ------- Relationships -------

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function createdExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'created_expense_id');
    }

    public function createdJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'created_journal_entry_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ------- Helpers -------

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function categoryLabel(): string
    {
        return Expense::CATEGORIES[$this->category] ?? ucfirst($this->category ?? 'Other');
    }

    public function typeLabel(): string
    {
        return ucfirst($this->type);
    }
}
