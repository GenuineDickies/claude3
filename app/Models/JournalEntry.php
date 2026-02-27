<?php

namespace App\Models;

use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JournalLine> $lines
 * @property-read int|null $lines_count
 * @property-read \App\Models\User|null $poster
 * @property-read Model|\Eloquent $source
 * @property-read \App\Models\User|null $voider
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry query()
 * @mixin \Eloquent
 */
class JournalEntry extends Model
{
    public const STATUS_DRAFT  = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID   = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_POSTED,
        self::STATUS_VOID,
    ];

    protected $fillable = [
        'entry_number',
        'entry_date',
        'memo',
        'reference',
        'source_type',
        'source_id',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at'  => 'datetime',
        ];
    }

    // ── Number generation ──────────────────────────────

    /**
     * Generate the next entry number in format JE-YYYYMMDD-XXXX.
     */
    public static function generateEntryNumber(): string
    {
        $prefix = 'JE-' . now()->format('Ymd') . '-';

        $latest = static::where('entry_number', 'like', $prefix . '%')
            ->orderByDesc('entry_number')
            ->value('entry_number');

        $seq = 1;
        if ($latest) {
            $seq = ((int) substr($latest, -4)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    // ── Helpers ────────────────────────────────────────

    /**
     * Check whether this entry's lines are balanced (debits == credits).
     */
    public function isBalanced(): bool
    {
        $totals = $this->lines()
            ->selectRaw('SUM(debit) as total_debits, SUM(credit) as total_credits')
            ->first();

        return round((float) $totals->total_debits, 2) === round((float) $totals->total_credits, 2);
    }

    /**
     * Void this entry. Voided entries are excluded from all balance calculations.
     */
    public function void(string $reason, ?int $userId = null): void
    {
        $this->update([
            'status'      => self::STATUS_VOID,
            'voided_by'   => $userId,
            'void_reason' => $reason,
        ]);
    }
}
