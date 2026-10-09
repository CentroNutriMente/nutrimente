<?php

namespace App\Models;

use App\Casts\Encrypted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'patient_id', 'type', 'title', 'description',
        'start_at', 'end_at', 'room', 'status', 'color',
        'is_shared', 'intervisione_id', 'cancellation_reason',
    ];

    protected $casts = [
        'start_at'            => 'datetime',
        'end_at'              => 'datetime',
        'is_shared'           => 'boolean',
        'title'               => Encrypted::class,
        'description'         => Encrypted::class,
        'cancellation_reason' => Encrypted::class,
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

    public function intervisione(): BelongsTo
    {
        return $this->belongsTo(Intervisione::class);
    }
}
