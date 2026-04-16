<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'uploaded_by', 'patient_id', 'template_id', 'title', 'description',
        'file_path', 'file_name', 'mime_type', 'file_size',
        'category', 'visible_to_categories', 'is_shared_with_patient',
        'deleted_at',
    ];

    protected $casts = [
        'visible_to_categories' => 'array',
        'is_shared_with_patient' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
