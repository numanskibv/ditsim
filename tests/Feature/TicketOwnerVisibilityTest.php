<?php

use App\Models\Ticket;
use App\Models\User;
use App\Support\CurrentStudent;

use function Pest\Laravel\actingAs;

it('shows the owning student of a ticket on the index for a shared role', function () {
    $student = User::factory()->technicus()->create(['name' => 'Bram Bewijs']);

    // A ticket inside the student's world.
    actingAs($student);
    Ticket::factory()->create(['title' => 'Eigenaar-zichtbaar']);

    // A docent (overview) sees the owner column populated.
    actingAs(User::factory()->docent()->create());
    Livewire::test('pages::tickets.index')
        ->assertSee('Eigenaar-zichtbaar')
        ->assertSee('Bram Bewijs');
});

it('shows the owning student on the ticket detail page', function () {
    $student = User::factory()->technicus()->create(['name' => 'Bram Bewijs']);

    actingAs($student);
    $ticket = Ticket::factory()->create();

    actingAs(User::factory()->docent()->create());
    app(CurrentStudent::class)->setActive($student->id);

    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->assertSee('Omgeving van')
        ->assertSee('Bram Bewijs');
});
