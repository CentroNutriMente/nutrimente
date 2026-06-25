<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMaterial extends Model
{
    protected $fillable = [
        'group_id', 'label', 'original_name', 'path', 'mime', 'size', 'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
