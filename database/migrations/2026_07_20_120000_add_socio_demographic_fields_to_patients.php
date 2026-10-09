<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Dati socio-demografici (cifrati a riposo)
            $table->text('stato_civile')->nullable()->after('medico_base');
            $table->text('educazione')->nullable()->after('stato_civile');
            $table->text('professione')->nullable()->after('educazione');
            // Parenti / tutori legali: JSON cifrato
            $table->text('relatives')->nullable()->after('professione');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['stato_civile', 'educazione', 'professione', 'relatives']);
        });
    }
};
