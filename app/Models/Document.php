<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property string $file_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $file_size
 * @property string $category
 * @property string|null $ai_summary
 * @property array<array-key, mixed>|null $ai_tags
 * @property int|null $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $documentable
 * @property-read \App\Models\User|null $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereAiSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereAiTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDocumentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUploadedBy($value)
 * @mixin \Eloquent
 */
class Document extends Model
{
    public const CATEGORIES = [
        'warranty_doc',
        'receipt',
        'invoice',
        'insurance',
        'license',
        'contract',
        'other',
    ];

    public const CATEGORY_LABELS = [
        'warranty_doc' => 'Warranty Document',
        'receipt'      => 'Receipt',
        'invoice'      => 'Invoice',
        'insurance'    => 'Insurance',
        'license'      => 'License',
        'contract'     => 'Contract',
        'other'        => 'Other',
    ];

    public const AI_STATUSES = ['pending', 'processing', 'completed', 'failed'];

    public const MATCH_STATUSES = ['unmatched', 'matched', 'manual', 'skipped'];

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'category',
        'ai_summary',
        'ai_tags',
        'ai_extracted_data',
        'ai_status',
        'ai_suggested_category',
        'ai_confidence',
        'ai_processed_at',
        'ai_error',
        'match_status',
        'match_candidates',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_tags'           => 'array',
            'ai_extracted_data' => 'array',
            'ai_processed_at'   => 'datetime',
            'ai_confidence'     => 'float',
            'match_candidates'  => 'array',
            'file_size'         => 'integer',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function transactionImports(): HasMany
    {
        return $this->hasMany(DocumentTransactionImport::class);
    }

    public function isAiCompleted(): bool
    {
        return $this->ai_status === 'completed';
    }

    public function isAiFailed(): bool
    {
        return $this->ai_status === 'failed';
    }

    public function isAiPending(): bool
    {
        return in_array($this->ai_status, ['pending', 'processing'], true);
    }

    /** Copy the AI-suggested category into the user-facing category field. */
    public function acceptAiCategory(): void
    {
        if ($this->ai_suggested_category) {
            $this->update(['category' => $this->ai_suggested_category]);
        }
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isWord(): bool
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true);
    }

    public function isSpreadsheet(): bool
    {
        return in_array($this->mime_type, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], true);
    }

    /** Human-readable file size. */
    public function humanFileSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /** Whether this document is in the inbox (not linked to any entity). */
    public function isInbox(): bool
    {
        return is_null($this->documentable_type) && is_null($this->documentable_id);
    }

    public function isUnmatched(): bool
    {
        return $this->match_status === 'unmatched';
    }

    public function isMatched(): bool
    {
        return in_array($this->match_status, ['matched', 'manual'], true);
    }

    /** Link this document to an entity. */
    public function linkTo(Model $entity, string $matchStatus = 'manual'): void
    {
        $this->update([
            'documentable_type' => $entity->getMorphClass(),
            'documentable_id'   => $entity->getKey(),
            'match_status'      => $matchStatus,
        ]);
    }

    /** Scope: only inbox (unlinked) documents. */
    public function scopeInbox($query)
    {
        return $query->whereNull('documentable_type');
    }

    /** Scope: only unmatched inbox documents. */
    public function scopeUnmatched($query)
    {
        return $query->whereNull('documentable_type')->where('match_status', 'unmatched');
    }
}
