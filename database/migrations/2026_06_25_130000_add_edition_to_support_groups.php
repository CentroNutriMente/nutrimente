<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_groups', function (Blueprint $table) {
            // Sottotitolo es. "Edizione 1 2026". La categoria non è più obbligatoria
            // (il titolo lo assegna la professionista).
            $table->string('edition')->nullable()->after('name');
            $table->string('category')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('support_groups', function (Blueprint $table) {
            $table->dropColumn('edition');
        });
    }
};
