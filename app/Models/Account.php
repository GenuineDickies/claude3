<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\JournalLine;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JournalLine> $journalLines
 * @property-read int|null $journal_lines_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @mixin \Eloquent
 */
class Account extends Model
{
    public const TYPE_ASSET     = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY    = 'equity';
    public const TYPE_REVENUE   = 'revenue';
    public const TYPE_COGS      = 'cogs';
    public const TYPE_EXPENSE   = 'expense';

    public const TYPES = [
        self::TYPE_ASSET,
        self::TYPE_LIABILITY,
        self::TYPE_EQUITY,
        self::TYPE_REVENUE,
        self::TYPE_COGS,
        self::TYPE_EXPENSE,
    ];

    public const TYPE_LABELS = [
        'asset'     => 'Asset',
        'liability' => 'Liability',
        'equity'    => 'Equity',
        'revenue'   => 'Revenue',
        'cogs'      => 'Cost of Goods Sold',
        'expense'   => 'Expense',
    ];

    /**
     * Account types with a normal debit balance.
     * All others have a normal credit balance.
     */
    public const DEBIT_NORMAL = ['asset', 'cogs', 'expense'];

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    // ── Helpers ────────────────────────────────────────

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Whether this account's balance increases with debits.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->type, self::DEBIT_NORMAL, true);
    }

    /**
     * Calculate the running balance for this account from posted journal entries.
     * Debit-normal accounts: debits − credits.
     * Credit-normal accounts: credits − debits.
     */
    public function balance(?\DateTimeInterface $asOf = null): float
    {
        $query = $this->journalLines()
            ->whereHas('journalEntry', function ($q) use ($asOf) {
                $q->where('status', JournalEntry::STATUS_POSTED);
                if ($asOf) {
                    $q->where('entry_date', '<=', $asOf);
                }
            });

        $debits  = (float) (clone $query)->sum('debit');
        $credits = (float) (clone $query)->sum('credit');

        return $this->isDebitNormal()
            ? round($debits - $credits, 2)
            : round($credits - $debits, 2);
    }
}
