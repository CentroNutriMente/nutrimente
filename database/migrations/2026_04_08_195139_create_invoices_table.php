<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // professionista emittente
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            // Numerazione progressiva per professionista: es. "1/2026"
            $table->integer('invoice_number');
            $table->integer('invoice_year');
            $table->string('invoice_code')->unique(); // "{number}/{year}_{user_id}"
            // Dati fiscali snapshot (al momento dell'emissione)
            $table->string('issuer_name');
            $table->string('issuer_partita_iva')->nullable();
            $table->string('issuer_codice_fiscale')->nullable();
            $table->string('issuer_address')->nullable();
            $table->string('issuer_regime_fiscale');
            $table->string('client_name');
            $table->string('client_codice_fiscale')->nullable();
            $table->string('client_address')->nullable();
            // Importi
            $table->decimal('subtotal', 10, 2);
            $table->decimal('marca_da_bollo', 10, 2)->default(0); // 2.00 se > 77.47
            $table->decimal('total', 10, 2);
            // IVA esente Art. 10
            $table->boolean('iva_exempt')->default(true);
            $table->string('iva_exemption_reason')->default('Art. 10 DPR 633/72');
            // STS (Sistema Tessera Sanitaria)
            $table->boolean('sts_sent')->default(false);
            $table->timestamp('sts_sent_at')->nullable();
            $table->string('payment_method')->nullable(); // contanti, bonifico, pos
            $table->date('issued_at');
            $table->date('paid_at')->nullable();
            $table->string('status')->default('draft'); // draft, issued, paid, cancelled
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
