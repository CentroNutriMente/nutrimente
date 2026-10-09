<?php

namespace App\Models;

use App\Casts\Encrypted;
use App\Casts\EncryptedArray;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'user_id', 'category', 'record_type', 'title',
        'data', 'notes', 'treatment_plan', 'record_date', 'is_shared_with_team',
    ];

    protected $casts = [
        'data'                => EncryptedArray::class,
        'title'               => Encrypted::class,
        'notes'               => Encrypted::class,
        'treatment_plan'      => Encrypted::class,
        'is_shared_with_team' => 'boolean',
        'record_date'         => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
