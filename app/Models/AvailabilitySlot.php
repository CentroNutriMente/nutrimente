<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailabilitySlot extends Model
{
    protected $fillable = [
        'user_id', 'day_of_week', 'start_time', 'end_time',
        'room', 'is_active', 'valid_from', 'valid_until',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'valid_from' => 'date',
        'valid_until'=> 'date',
    ];

    // 0=Mon … 6=Sun (matching the migration comment)
    public static array $DAY_LABELS = ['Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato','Domenica'];
}
