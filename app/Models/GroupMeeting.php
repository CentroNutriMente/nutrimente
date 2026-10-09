<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupMeeting extends Model
{
    protected $fillable = [
        'group_id', 'title', 'scheduled_at', 'duration_minutes', 'notes',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'duration_minutes' => 'integer',
    ];

    /**
     * Vedi Appointment::serializeDate — orario "da parete" senza suffisso Z.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(GroupAttendance::class);
    }
}
