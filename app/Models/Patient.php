<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Report;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'codice_fiscale', 'date_of_birth', 'gender',
        'email', 'phone', 'address', 'city', 'cap',
        'emergency_contact_name', 'emergency_contact_phone',
        'medico_base', 'notes', 'is_active', 'booking_token', 'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Patient $patient) {
            if (empty($patient->booking_token)) {
                $patient->booking_token = Str::random(32);
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'patient_professional');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PatientTag::class, 'patient_tag');
    }

    public function records(): HasMany
    {
        return $this->hasMany(PatientRecord::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
