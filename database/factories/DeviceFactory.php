<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\Rack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->numberBetween(1, 40);

        return [
            'rack_id' => Rack::factory(),
            'owner_id' => null,
            'name' => fake()->randomElement(['srv', 'sw', 'db', 'fw']).'-'.fake()->numberBetween(1, 99),
            'type' => fake()->randomElement(DeviceType::cases()),
            'status' => fake()->randomElement(DeviceStatus::cases()),
            'u_start' => $start,
            'u_end' => $start + fake()->numberBetween(0, 2),
            'cpu' => fake()->numberBetween(10, 50),
            'temp' => fake()->numberBetween(30, 50),
            'metric_trend' => 0,
        ];
    }

    /**
     * Place the device at a specific U range.
     */
    public function atPosition(int $start, int $end): static
    {
        return $this->state(fn (array $attributes): array => [
            'u_start' => $start,
            'u_end' => $end,
        ]);
    }
}
