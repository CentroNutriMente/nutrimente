<?php

namespace App\Models;

use App\Casts\Encrypted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'user_id', 'body', 'is_private',
    ];

    protected $casts = [
        'body'       => Encrypted::class,
        'is_private' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
