<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fictional demo data for walking through every portal. All accounts use the password "password".
 */
class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->platformAdmin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@demo.test',
        ]);

        User::factory()->employerAdmin()->create([
            'name' => 'Huda Al Balushi',
            'email' => 'hr@alnoor.demo.test',
        ]);

        User::factory()->partnerViewer()->create([
            'name' => 'Bank Viewer',
            'email' => 'bank@demo.test',
        ]);

        User::factory()->employee()->create([
            'name' => 'Salim Al Harthy',
            'email' => 'salim@alnoor.demo.test',
        ]);
    }
}
