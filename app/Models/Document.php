<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
