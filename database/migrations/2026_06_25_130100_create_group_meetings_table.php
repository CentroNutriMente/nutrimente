<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('support_groups')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_meetings');
    }
};
