<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupAttendance extends Model
{
    protected $fillable = [
        'group_meeting_id', 'group_participant_id', 'present', 'marked_by',
    ];

    protected $casts = [
        'present' => 'boolean',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(GroupMeeting::class, 'group_meeting_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(GroupParticipant::class, 'group_participant_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
