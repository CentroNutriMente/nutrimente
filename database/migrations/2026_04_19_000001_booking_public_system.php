<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add public slug to professional profiles
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('user_id');
        });

        // Backfill slugs for existing profiles
        \App\Models\ProfessionalProfile::with('user')->get()->each(function ($p) {
            $base = Str::slug($p->user->name);
            $slug = $base;
            $i = 2;
            while (\App\Models\ProfessionalProfile::where('slug', $slug)->where('id', '!=', $p->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $p->update(['slug' => $slug]);
        });

        // Booking requests from the public form
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('patient_name');
            $table->string('patient_surname');
            $table->string('patient_email');
            $table->string('patient_phone')->nullable();
            $table->text('notes')->nullable();
            $table->date('requested_date');
            $table->time('requested_time');
            $table->string('status')->default('pending'); // pending | confirmed | rejected
            $table->string('confirm_token', 64)->unique()->nullable();
            $table->string('reject_token', 64)->unique()->nullable();
            $table->string('invite_token', 64)->unique()->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
        Schema::table('professional_profiles', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
