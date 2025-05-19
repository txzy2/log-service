<?php

namespace Database\Factories;

use App\Models\Services;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServicesFactory extends Factory
{
    protected $model = Services::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'active' => 'Y',
        ];
    }
}