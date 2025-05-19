<?php

namespace Database\Seeders;

use App\Models\Services;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Services::factory()->create([
            'name' => "WSPG",
            'active' => "Y",
        ]);

        Services::factory()->create([
            'name' => 'ADS',
            'active' => "Y",
        ]);
    }
}
