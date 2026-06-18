<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRequest extends Model
{
    protected $fillable = [
        'professional_id', 'name', 'surname', 'phone', 'email',
        'how_found', 'contact_method', 'availability', 'notes',
        'status', 'accepted_by',
        'patient_id', 'appointment_id', 'confirm_token',
    ];

    protected $casts = [
        'how_found'      => 'array',
        'contact_method' => 'array',
        'availability'   => 'array',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function fullName(): string
    {
        return trim("{$this->name} {$this->surname}");
    }
}
