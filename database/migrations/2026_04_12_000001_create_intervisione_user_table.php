<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervisione_user', function (Blueprint $table) {
            $table->foreignId('intervisione_id')->constrained('intervisioni')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['intervisione_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervisione_user');
    }
};
