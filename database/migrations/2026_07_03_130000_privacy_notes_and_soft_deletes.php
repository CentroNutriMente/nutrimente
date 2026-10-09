<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Nota clinica "riservata" (visibile solo all'autore): clinical_notes.is_private.
 * - Estende il soft-delete (cancellazione reversibile e tracciabile) ai dati clinici
 *   che oggi vengono eliminati definitivamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('body');
            $table->softDeletes();
        });

        foreach (['reports', 'questionnaires', 'appointments', 'invoices'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->dropColumn(['is_private', 'deleted_at']);
        });

        foreach (['reports', 'questionnaires', 'appointments', 'invoices'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
