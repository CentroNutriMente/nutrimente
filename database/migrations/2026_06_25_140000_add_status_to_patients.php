<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Stato a 3 valori gestito dal professionista: attivo|sospeso|concluso.
            // is_active resta in sync (true solo se "attivo") per dashboard/liste esistenti.
            $table->string('status')->default('attivo')->after('is_active');
        });

        DB::table('patients')->where('is_active', false)->update(['status' => 'sospeso']);
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
