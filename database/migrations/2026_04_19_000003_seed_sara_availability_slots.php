<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find Sara's user via her professional profile slug
        $profile = DB::table('professional_profiles')->where('slug', 'sara-alessandri')->first();
        if (! $profile) return;

        $userId = $profile->user_id;

        // Skip if slots already exist for this user
        if (DB::table('availability_slots')->where('user_id', $userId)->exists()) return;

        // Mon=0, Thu=3, Fri=4  |  10:00-12:00 and 16:00-18:00
        $slots = [];
        foreach ([0, 3, 4] as $day) {
            foreach ([['10:00:00', '12:00:00'], ['16:00:00', '18:00:00']] as [$start, $end]) {
                $slots[] = [
                    'user_id'     => $userId,
                    'day_of_week' => $day,
                    'start_time'  => $start,
                    'end_time'    => $end,
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        DB::table('availability_slots')->insert($slots);
    }

    public function down(): void {}
};
