<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Il campo "Prossimo appuntamento" del referto diventa un vero datetime che genera
 * un appuntamento in agenda: memorizziamo la data/ora scelta e l'id dell'appuntamento
 * creato (per aggiornarlo/rimuoverlo in modifica senza duplicati).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dateTime('next_appointment_at')->nullable()->after('next_appointment');
            $table->foreignId('next_appointment_id')->nullable()->after('next_appointment_at')
                ->constrained('appointments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_appointment_id');
            $table->dropColumn('next_appointment_at');
        });
    }
};
