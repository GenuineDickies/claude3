<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccountingLink extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_type',
        'document_id',
        'journal_entry_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the source document (polymorphic manual resolve).
     */
    public function document(): ?Model
    {
        if (! $this->document_type || ! $this->document_id) {
            return null;
        }

        return $this->document_type::find($this->document_id);
    }
}
