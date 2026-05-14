<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Invoice;

class ProfessionalProfile extends Model
{
    protected $fillable = [
        'user_id', 'category', 'title', 'bio', 'curriculum', 'specializations',
        'photo', 'partita_iva', 'codice_fiscale', 'regime_fiscale',
        'cassa_previdenziale', 'albo_professionale', 'numero_albo',
        'invoice_counter', 'is_bookable', 'is_founder', 'session_duration_minutes',
        'session_price', 'booking_notes', 'phone', 'website', 'address', 'slug',
    ];

    protected $casts = [
        'specializations' => 'array',
        'is_bookable' => 'boolean',
        'is_founder'  => 'boolean',
        'session_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nextInvoiceNumber(): int
    {
        $year = now()->year;
        $maxExisting = Invoice::where('user_id', $this->user_id)
            ->where('invoice_year', $year)
            ->max('invoice_number') ?? 0;
        $next = max($this->invoice_counter, $maxExisting) + 1;
        $this->update(['invoice_counter' => $next]);
        return $next;
    }
}
