<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // professionista che ha scritto
            $table->string('category'); // psicologo, nutrizionista, osteopata, ecc.
            $table->string('record_type'); // anamnesi, seduta, valutazione, follow-up
            $table->string('title');
            $table->json('data'); // campi modulari per categoria (flessibile)
            $table->text('notes')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->date('record_date');
            $table->boolean('is_shared_with_team')->default(false); // intervisione
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_records');
    }
};
