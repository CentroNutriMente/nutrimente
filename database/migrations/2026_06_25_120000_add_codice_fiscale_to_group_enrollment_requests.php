<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_enrollment_requests', function (Blueprint $table) {
            $table->string('codice_fiscale', 16)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('group_enrollment_requests', function (Blueprint $table) {
            $table->dropColumn('codice_fiscale');
        });
    }
};
