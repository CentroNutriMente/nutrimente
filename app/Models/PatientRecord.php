<?php

namespace App\Models;

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
        'data' => 'array',
        'is_shared_with_team' => 'boolean',
        'record_date' => 'date',
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
