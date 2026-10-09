<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_meeting_id')->constrained('group_meetings')->cascadeOnDelete();
            $table->foreignId('group_participant_id')->constrained('group_participants')->cascadeOnDelete();
            $table->boolean('present')->default(false);
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Una sola registrazione per incontro/partecipante; l'assenza di riga
            // significa "presenza non ancora registrata".
            $table->unique(['group_meeting_id', 'group_participant_id'], 'group_attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_attendances');
    }
};
