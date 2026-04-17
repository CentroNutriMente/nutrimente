<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    protected $fillable = [
        'created_by', 'title', 'category', 'content',
        'platforms', 'status', 'scheduled_at', 'notes',
    ];

    protected $casts = [
        'platforms'    => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
