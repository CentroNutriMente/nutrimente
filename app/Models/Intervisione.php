<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Intervisione extends Model
{
    protected $table = 'intervisioni';

    protected $fillable = [
        'created_by', 'patient_id', 'title', 'description',
        'discussion_notes', 'conclusions', 'status', 'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'intervisione_user');
    }
}
