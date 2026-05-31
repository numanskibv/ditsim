<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CurrentStudent;

use function Pest\Laravel\actingAs;

it('shows the technicus their portfolio progress and open tickets', function () {
    $tech = User::factory()->technicus()->create();

    actingAs($tech);
    Ticket::factory()->create(['title' => 'OpenItem', 'status' => TicketStatus::Open]);

    Livewire::test('dashboard-overview')
        ->assertSee('Mijn examenvoortgang')
        ->assertSee('Opdracht 1')
        ->assertSee('Mijn openstaande tickets')
        ->assertSee('OpenItem');
});

it('shows the docent a class overview with each student', function () {
    User::factory()->technicus()->create(['name' => 'Sanne Klasrij']);

    actingAs(User::factory()->docent()->create());

    Livewire::test('dashboard-overview')
        ->assertSee('Klasoverzicht')
        ->assertSee('Sanne Klasrij');
});

it('shows the leidinggevende a review to-do list with the owning student', function () {
    $student = User::factory()->technicus()->create(['name' => 'Tom Tickethouder']);

    // A ticket awaiting four-eyes approval in the student's world.
    actingAs($student);
    Ticket::factory()->create(['status' => TicketStatus::WachtenOpControle]);

    actingAs(User::factory()->leidinggevende()->create());
    Livewire::test('dashboard-overview')
        ->assertSee('Tickets te controleren')
        ->assertSee('Tom Tickethouder');
});

it('shows the klant only their own reports', function () {
    $klant = User::factory()->klant()->create();
    $student = User::factory()->technicus()->create();

    actingAs($klant);
    app(CurrentStudent::class)->setActive($student->id);
    $mine = Ticket::factory()->create(['title' => 'MijnMelding', 'created_by' => $klant->id]);

    // A ticket from someone else must not appear.
    actingAs($student);
    Ticket::factory()->create(['title' => 'NietVanMij']);

    actingAs($klant);
    Livewire::test('dashboard-overview')
        ->assertSee('Mijn meldingen')
        ->assertSee('MijnMelding')
        ->assertDontSee('NietVanMij');
});
