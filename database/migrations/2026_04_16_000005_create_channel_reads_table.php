<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel_type'); // team | direct
            $table->string('channel_id');
            $table->timestamp('last_read_at');
            $table->unique(['user_id', 'channel_type', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_reads');
    }
};
