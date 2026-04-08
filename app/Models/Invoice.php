<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    const MARCA_DA_BOLLO_THRESHOLD = 77.47;
    const MARCA_DA_BOLLO_AMOUNT = 2.00;

    protected $fillable = [
        'user_id', 'patient_id', 'appointment_id',
        'invoice_number', 'invoice_year', 'invoice_code',
        'issuer_name', 'issuer_partita_iva', 'issuer_codice_fiscale',
        'issuer_address', 'issuer_regime_fiscale',
        'client_name', 'client_codice_fiscale', 'client_address',
        'subtotal', 'marca_da_bollo', 'total',
        'iva_exempt', 'iva_exemption_reason',
        'sts_sent', 'sts_sent_at',
        'payment_method', 'issued_at', 'paid_at', 'status', 'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'paid_at' => 'date',
        'sts_sent_at' => 'datetime',
        'iva_exempt' => 'boolean',
        'sts_sent' => 'boolean',
        'subtotal' => 'decimal:2',
        'marca_da_bollo' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public static function calculateMarcaDaBollo(float $subtotal): float
    {
        return $subtotal > self::MARCA_DA_BOLLO_THRESHOLD ? self::MARCA_DA_BOLLO_AMOUNT : 0.0;
    }
}
