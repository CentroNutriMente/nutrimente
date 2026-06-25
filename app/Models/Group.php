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
        'modality', 'location', 'capacity', 'next_meeting_at', 'status',
        'public_token', 'created_by',
    ];

    protected $casts = [
        'next_meeting_at' => 'datetime',
        'capacity'        => 'integer',
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

    // Tono cromatico (le categorie fisse non esistono più): rotazione per id,
    // così le card hanno varietà visiva mantenendo la palette del brand.
    public function getToneAttribute(): string
    {
        $tones = ['lavender', 'sage', 'blush'];

        return $tones[$this->id % count($tones)];
    }
}
