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
        'category', 'name', 'description', 'leader_user_id', 'cadence',
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

    public function categoryConfig(): array
    {
        return config("groups.categories.{$this->category}", [
            'label' => $this->category,
            'tone'  => 'sage',
            'description' => null,
        ]);
    }
}
