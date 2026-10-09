<?php

namespace App\Models;

use App\Casts\Encrypted;
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
        'name'            => Encrypted::class,
        'email'           => Encrypted::class,
        'phone'           => Encrypted::class,
        'codice_fiscale'  => Encrypted::class,
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
