<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupEnrollmentRequest extends Model
{
    protected $fillable = [
        'group_id', 'category', 'name', 'email', 'phone', 'codice_fiscale',
        'how_heard', 'privacy_consent', 'source', 'status',
    ];

    protected $casts = [
        'privacy_consent' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
