<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocumentAttachment extends Model
{
    protected $fillable = [
        'vendor_document_id',
        'file_path',
        'file_type',
        'original_filename',
        'file_size',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function vendorDocument(): BelongsTo
    {
        return $this->belongsTo(VendorDocument::class);
    }
}
