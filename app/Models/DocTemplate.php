<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocTemplate extends Model
{
    protected $table = 'doc_templates';

    protected $fillable = ['created_by', 'name', 'description', 'is_system', 'content'];

    protected $casts = [
        'content'   => 'array',
        'is_system' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
