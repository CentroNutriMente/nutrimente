<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_groups', function (Blueprint $table) {
            // Quota a persona per singolo incontro + durata dell'incontro:
            // servono al form pubblico d'iscrizione per far capire l'impegno.
            $table->decimal('price_per_session', 8, 2)->nullable()->after('capacity');
            $table->unsignedSmallInteger('session_duration_minutes')->nullable()->after('price_per_session');
        });

        // Gruppi già esistenti: 35 € a incontro di due ore, a persona.
        DB::table('support_groups')
            ->whereNull('price_per_session')
            ->update(['price_per_session' => 35.00, 'session_duration_minutes' => 120]);
    }

    public function down(): void
    {
        Schema::table('support_groups', function (Blueprint $table) {
            $table->dropColumn(['price_per_session', 'session_duration_minutes']);
        });
    }
};
