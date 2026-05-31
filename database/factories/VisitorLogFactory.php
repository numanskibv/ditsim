<?php

namespace Database\Factories;

use App\Models\VisitorLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitorLog>
 */
class VisitorLogFactory extends Factory
{
    /**
     * Define the model's default state (an open, checked-in visit).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_name' => fake()->name(),
            'company' => fake()->company(),
            'reason' => fake()->sentence(3),
            'badge_number' => 'B-'.fake()->numberBetween(100, 999),
            'escort_id' => null,
            'checked_in_at' => now(),
            'checked_out_at' => null,
        ];
    }

    /**
     * A fully completed visit (checked in and out).
     */
    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'checked_out_at' => now(),
        ]);
    }
}
