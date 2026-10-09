<?php

namespace App\Models;

use App\Casts\Encrypted;
use App\Casts\EncryptedArray;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'patient_id', 'report_template_id', 'title',
        'report_date', 'sections_data', 'next_appointment', 'next_appointment_at',
        'next_appointment_id', 'notes', 'pdf_path',
    ];

    protected $casts = [
        'report_date'         => 'datetime',
        'next_appointment_at' => 'datetime',
        'sections_data'       => EncryptedArray::class,
        'title'               => Encrypted::class,
        'next_appointment'    => Encrypted::class,
        'notes'               => Encrypted::class,
    ];

    /**
     * Serializza le date come orario "da parete" (senza suffisso Z), così che
     * new Date() nel frontend le legga come ora locale e non aggiunga l'offset
     * del fuso (es. +2h in estate), mantenendo l'orario salvato.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'next_appointment_id');
    }
}
