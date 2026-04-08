<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->withPersonalTeam()->create([
            'name' => 'Admin',
            'email' => 'admin@centronutrimento.it',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');
    }
}
