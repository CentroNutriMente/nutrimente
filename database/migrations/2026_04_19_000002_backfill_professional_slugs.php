<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        \App\Models\ProfessionalProfile::with('user')->get()->each(function ($p) {
            if ($p->slug) return; // already set
            $base = Str::slug($p->user->name);
            $slug = $base; $i = 2;
            while (\App\Models\ProfessionalProfile::where('slug', $slug)->where('id', '!=', $p->id)->exists()) {
                $slug = $base . '-' . $i++;
            }
            \DB::table('professional_profiles')->where('id', $p->id)->update(['slug' => $slug]);
        });
    }

    public function down(): void {}
};
