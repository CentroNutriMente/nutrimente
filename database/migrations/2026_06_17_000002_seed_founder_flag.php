<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mark Sara Alessandri's profile as founder so she becomes the triage hub
        // for incoming first-contact requests. Identified by her public slug,
        // the same identifier used to seed her availability slots.
        DB::table('professional_profiles')
            ->where('slug', 'sara-alessandri')
            ->update(['is_founder' => true]);
    }

    public function down(): void
    {
        DB::table('professional_profiles')
            ->where('slug', 'sara-alessandri')
            ->update(['is_founder' => false]);
    }
};
