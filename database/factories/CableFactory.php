<?php

namespace Database\Factories;

use App\Enums\CableMedium;
use App\Models\Cable;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cable>
 */
class CableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => 'K-'.fake()->unique()->numberBetween(1, 999),
            'medium' => fake()->randomElement(CableMedium::cases()),
            'color' => fake()->safeColorName(),
            'from_device_id' => Device::factory(),
            'from_port' => fake()->numberBetween(1, 4),
            'to_device_id' => Device::factory(),
            'to_port' => fake()->numberBetween(1, 4),
        ];
    }

    /**
     * Connect a specific from/to endpoint.
     */
    public function between(Device $from, int $fromPort, Device $to, int $toPort): static
    {
        return $this->state(fn (array $attributes): array => [
            'from_device_id' => $from->id,
            'from_port' => $fromPort,
            'to_device_id' => $to->id,
            'to_port' => $toPort,
        ]);
    }
}
