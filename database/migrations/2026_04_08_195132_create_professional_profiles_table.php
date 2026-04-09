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
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // psicologo, nutrizionista, osteopata, ecc.
            $table->string('title')->nullable(); // Dott., Dott.ssa
            $table->text('bio')->nullable();
            $table->text('curriculum')->nullable();
            $table->json('specializations')->nullable();
            $table->string('photo')->nullable();
            // Dati fiscali
            $table->string('partita_iva')->nullable();
            $table->string('codice_fiscale')->nullable();
            $table->string('regime_fiscale')->default('ordinario');
            $table->string('cassa_previdenziale')->nullable();
            $table->string('albo_professionale')->nullable();
            $table->string('numero_albo')->nullable();
            // Fatturazione: numerazione progressiva per professionista
            $table->integer('invoice_counter')->default(0);
            // Booking
            $table->boolean('is_bookable')->default(true);
            $table->integer('session_duration_minutes')->default(50);
            $table->decimal('session_price', 8, 2)->nullable();
            $table->text('booking_notes')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};
