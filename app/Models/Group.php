<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $table = 'support_groups';

    protected $fillable = [
        'category', 'name', 'edition', 'description', 'leader_user_id', 'cadence',
        'modality', 'location', 'capacity', 'price_per_session', 'session_duration_minutes',
        'next_meeting_at', 'status', 'public_token', 'created_by',
    ];

    protected $casts = [
        'next_meeting_at'          => 'datetime',
        'capacity'                 => 'integer',
        'price_per_session'        => 'decimal:2',
        'session_duration_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            if (empty($group->public_token)) {
                $group->public_token = Str::lower(Str::random(12));
            }
        });
    }

    // ── Relazioni ────────────────────────────────────────────────────────────
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupParticipant::class);
    }

    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(GroupEnrollmentRequest::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(GroupMeeting::class)->orderBy('scheduled_at');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(GroupMaterial::class)->orderByDesc('created_at');
    }

    // ── Accessor / helper ────────────────────────────────────────────────────
    public function getEnrolledCountAttribute(): int
    {
        return $this->participants_count
            ?? $this->participants()->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, (int) $this->capacity - $this->enrolled_count);
    }

    /** Durata di un incontro in forma leggibile: "2 ore", "1h 30min". */
    public function getDurationLabelAttribute(): ?string
    {
        $minutes = $this->session_duration_minutes;

        if (! $minutes) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;

        if ($hours && $rest) {
            return "{$hours}h {$rest}min";
        }

        if ($hours) {
            return $hours === 1 ? '1 ora' : "{$hours} ore";
        }

        return "{$rest} minuti";
    }

    /** Es. "35 € a incontro (2 ore), a persona". Null se la quota non è impostata. */
    public function getPriceLabelAttribute(): ?string
    {
        if ($this->price_per_session === null) {
            return null;
        }

        $amount = number_format((float) $this->price_per_session, 2, ',', '.');
        $amount = str_ends_with($amount, ',00') ? substr($amount, 0, -3) : $amount;

        $label = "{$amount} € a incontro";

        if ($this->duration_label) {
            $label .= " ({$this->duration_label})";
        }

        return "{$label}, a persona";
    }

    // Tono cromatico (le categorie fisse non esistono più): rotazione per id,
    // così le card hanno varietà visiva mantenendo la palette del brand.
    public function getToneAttribute(): string
    {
        $tones = ['lavender', 'sage', 'blush'];

        return $tones[$this->id % count($tones)];
    }
}
