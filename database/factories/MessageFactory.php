<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_id' => User::factory(),
            'to_id' => User::factory(),
            'ticket_id' => null,
            'body' => fake()->sentence(),
            'sent_at' => now(),
        ];
    }
}
