<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLineItem extends Model
{
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = ['draft', 'accepted', 'rejected'];

    protected $fillable = [
        'document_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'category',
        'account_id',
        'status',
        'created_journal_entry_id',
        'reviewed_by',
        'reviewed_at',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'decimal:3',
            'unit_price'  => 'decimal:2',
            'amount'      => 'decimal:2',
            'raw_data'    => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'created_journal_entry_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function categoryLabel(): string
    {
        return Expense::CATEGORIES[$this->category] ?? ucfirst($this->category ?? 'Uncategorized');
    }
}
