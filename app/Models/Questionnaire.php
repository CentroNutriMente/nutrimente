<?php

namespace App\Models;

use App\Casts\Encrypted;
use App\Casts\EncryptedArray;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Questionnaire extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'patient_id', 'questionnaire_template_id', 'report_id',
        'filled_at', 'answers', 'total_score', 'notes',
    ];

    protected $casts = [
        'answers'   => EncryptedArray::class,
        'notes'     => Encrypted::class,
        'filled_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class, 'questionnaire_template_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
