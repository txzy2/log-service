<?php

namespace Database\Seeders;

use App\Models\IncidentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IncidentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        IncidentType::factory()->create([
            'type_name' => 'Block Acc',
            'send_template_id' => 1,
            'code' => '500.1.26',
            'lifecycle' => 7,
            'alias' => 'manager'
        ]);
    }
}
