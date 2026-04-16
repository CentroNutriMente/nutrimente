<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->string('regime_fiscale')->nullable()->default(null)->change();
            $table->integer('session_duration_minutes')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->string('regime_fiscale')->nullable(false)->default('ordinario')->change();
            $table->integer('session_duration_minutes')->nullable(false)->default(50)->change();
        });
    }
};
