<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('support_groups')->cascadeOnDelete();
            // Partecipante: paziente esistente (patient_id) OPPURE contatto esterno (campi denormalizzati).
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('in_attesa'); // confermata|in_attesa|pagato
            $table->dateTime('joined_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_participants');
    }
};
