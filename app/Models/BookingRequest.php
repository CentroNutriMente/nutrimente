<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRequest extends Model
{
    protected $fillable = [
        'professional_id', 'patient_name', 'patient_surname',
        'patient_email', 'patient_phone', 'notes',
        'requested_date', 'requested_time', 'status',
        'confirm_token', 'reject_token', 'invite_token',
        'confirmed_at', 'appointment_id',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'confirmed_at'   => 'datetime',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function fullName(): string
    {
        return "{$this->patient_name} {$this->patient_surname}";
    }
}
