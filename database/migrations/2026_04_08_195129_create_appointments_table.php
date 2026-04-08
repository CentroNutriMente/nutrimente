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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // professionista
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('session'); // session, intervision, personal, blocked
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('room')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, confirmed, cancelled, completed
            $table->string('color')->nullable(); // per categoria sul calendario
            $table->boolean('is_shared')->default(false); // visibile a tutto il team
            $table->foreignId('intervisione_id')->nullable()->constrained('intervisioni')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
