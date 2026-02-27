<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'ai_tags'   => 'array',
            'file_size' => 'integer',
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
}
