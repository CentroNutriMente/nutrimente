<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientConsent extends Model
{
    protected $fillable = [
        'patient_id', 'user_id', 'type', 'accepted',
        'accepted_at', 'method', 'document_path', 'ip_address',
    ];

    protected $casts = [
        'accepted'    => 'boolean',
        'accepted_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
