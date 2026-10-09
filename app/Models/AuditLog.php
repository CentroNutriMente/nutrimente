<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /** Solo created_at (i log sono immutabili). */
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra un evento nel registro operazioni.
     *
     * @param  string  $action  login, logout, login_failed, viewed, created, updated, deleted, downloaded, exported…
     */
    public static function record(string $action, ?Model $model = null, ?array $old = null, ?array $new = null): void
    {
        $request = request();

        static::create([
            'user_id'    => auth()->id() ?? ($model instanceof User ? $model->getKey() : null),
            'action'     => $action,
            'model_type' => $model ? $model::class : null,
            'model_id'   => $model?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
