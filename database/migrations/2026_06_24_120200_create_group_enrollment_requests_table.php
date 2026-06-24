<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_enrollment_requests', function (Blueprint $table) {
            $table->id();
            // Richiesta d'iscrizione dal form pubblico / QR. Può puntare a un gruppo
            // specifico oppure solo a una categoria d'interesse.
            $table->foreignId('group_id')->nullable()->constrained('support_groups')->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('how_heard')->nullable();
            $table->boolean('privacy_consent')->default(false);
            $table->string('source')->default('form');  // form|qr
            $table->string('status')->default('da_contattare');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_enrollment_requests');
    }
};
