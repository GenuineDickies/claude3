<?php

namespace App\Models;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Account|null $account
 * @property-read JournalEntry|null $journalEntry
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalLine query()
 * @mixin \Eloquent
 */
class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'debit'  => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
