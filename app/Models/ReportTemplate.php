<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'header_title', 'header_subtitle',
        'header_logo', 'sections', 'show_patient_fields', 'show_professional_footer',
        'footer_note', 'is_default',
    ];

    protected $casts = [
        'sections' => 'array',
        'show_patient_fields' => 'boolean',
        'show_professional_footer' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
