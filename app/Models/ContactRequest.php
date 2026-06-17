<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRequest extends Model
{
    protected $fillable = [
        'name', 'surname', 'phone', 'email',
        'how_found', 'contact_method', 'availability', 'notes',
        'status', 'assigned_to', 'accepted_by',
        'patient_id', 'appointment_id', 'confirm_token',
    ];

    protected $casts = [
        'how_found'      => 'array',
        'contact_method' => 'array',
        'availability'   => 'array',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    /**
     * The triage hub user: every incoming first-contact request lands in their
     * inbox. Resolved as the founder, with a fallback on the well-known slug.
     */
    public static function triageUser(): ?User
    {
        $profile = ProfessionalProfile::where('is_founder', true)->first()
            ?? ProfessionalProfile::where('slug', 'sara-alessandri')->first();

        return $profile?->user;
    }
}
