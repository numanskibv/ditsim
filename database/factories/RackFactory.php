<?php

namespace Database\Factories;

use App\Models\Rack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rack>
 */
class RackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'R'.fake()->unique()->numberBetween(1, 99),
            'location' => fake()->randomElement(['DC-Utrecht', 'DC-Amsterdam', 'DC-Rotterdam']),
            'height_u' => 42,
        ];
    }
}
