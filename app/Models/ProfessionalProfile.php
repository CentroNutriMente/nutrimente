<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalProfile extends Model
{
    protected $fillable = [
        'user_id', 'category', 'title', 'bio', 'curriculum', 'specializations',
        'photo', 'partita_iva', 'codice_fiscale', 'regime_fiscale',
        'cassa_previdenziale', 'albo_professionale', 'numero_albo',
        'invoice_counter', 'is_bookable', 'session_duration_minutes',
        'session_price', 'booking_notes', 'phone', 'website', 'address',
    ];

    protected $casts = [
        'specializations' => 'array',
        'is_bookable' => 'boolean',
        'session_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nextInvoiceNumber(): int
    {
        $this->increment('invoice_counter');
        return $this->invoice_counter;
    }
}
