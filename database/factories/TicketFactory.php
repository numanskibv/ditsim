<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The ticket number and sla_minutes are filled automatically by the
     * model's lifecycle hooks, so they are intentionally omitted here.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(TicketType::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Open,
            'priority' => fake()->randomElement(TicketPriority::cases()),
        ];
    }

    /**
     * Assign the ticket to the given executor.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes): array => ['assigned_to' => $user->id]);
    }

    /**
     * Give the ticket a distinct checker (satisfies the four-eyes rule).
     */
    public function checkedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => ['checked_by' => $user->id]);
    }

    /**
     * Set a specific priority.
     */
    public function priority(TicketPriority $priority): static
    {
        return $this->state(fn (array $attributes): array => ['priority' => $priority]);
    }

    /**
     * Mark the ticket closed at a specific moment.
     */
    public function closedAt(\DateTimeInterface $closedAt): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Afgesloten,
            'closed_at' => $closedAt,
        ]);
    }
}
