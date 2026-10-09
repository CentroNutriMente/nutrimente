<?php

namespace App\Models;

use App\Casts\Encrypted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupParticipant extends Model
{
    protected $fillable = [
        'group_id', 'patient_id', 'name', 'email', 'phone', 'status', 'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'name'      => Encrypted::class,
        'email'     => Encrypted::class,
        'phone'     => Encrypted::class,
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(GroupAttendance::class, 'group_participant_id');
    }
}
