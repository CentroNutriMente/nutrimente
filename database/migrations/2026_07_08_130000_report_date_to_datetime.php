<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La "Data referto" include ora anche l'orario: report_date da DATE a DATETIME.
 * I valori esistenti mantengono la data con orario 00:00.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dateTime('report_date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->date('report_date')->change();
        });
    }
};
