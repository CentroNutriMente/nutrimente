<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "support_groups" (non "groups": GROUPS è keyword riservata in MySQL 8).
        Schema::create('support_groups', function (Blueprint $table) {
            $table->id();
            $table->string('category');               // chiave config/groups.php (ansia|caregiver|alimentazione)
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('leader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cadence')->nullable();     // settimanale|quindicinale|mensile
            $table->string('modality')->nullable();    // presenza|online
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->default(12);
            $table->dateTime('next_meeting_at')->nullable();
            $table->string('status')->default('attivo'); // attivo|in_partenza|concluso
            $table->string('public_token', 40)->unique(); // URL pubblico + QR
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_groups');
    }
};
