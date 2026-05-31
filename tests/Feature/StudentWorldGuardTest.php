<?php

use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CurrentStudent;

use function Pest\Laravel\actingAs;

it('blocks a klant from creating a ticket without a chosen student world', function () {
    actingAs(User::factory()->klant()->create());

    Livewire::test('tickets.ticket-form')
        ->call('openCreate')
        ->set('title', 'Storing zonder wereld')
        ->set('type', TicketType::Incident->value)
        ->set('priority', TicketPriority::P1->value)
        ->call('save')
        ->assertHasErrors('student_world');

    expect(Ticket::withoutGlobalScopes()->count())->toBe(0);
});

it('blocks a leidinggevende from approving without a chosen student world', function () {
    $ticket = Ticket::factory()->create();

    actingAs(User::factory()->leidinggevende()->create());
    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->call('approve')
        ->assertHasErrors('student_world');

    expect($ticket->fresh()->approved_by)->toBeNull();
});

it('does not block a technicus acting in their own world', function () {
    $ticket = Ticket::factory()->create(['status' => \App\Enums\TicketStatus::Open]);

    actingAs(User::factory()->technicus()->create());
    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->call('advanceStatus')
        ->assertHasNoErrors();
});

it('lets a coupled student approve in the partner world through the UI', function () {
    $x = User::factory()->technicus()->create();
    $y = User::factory()->technicus()->create();
    $x->forceFill(['partner_id' => $y->id])->save();
    $y->forceFill(['partner_id' => $x->id])->save();

    // A ticket in Y's world.
    actingAs($y);
    $ticket = Ticket::factory()->create();

    // X acts as the counter-role (leidinggevende) inside Y's world.
    actingAs($x->fresh());
    app(CurrentStudent::class)->setActive($y->id);

    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->call('approve')
        ->assertHasNoErrors();

    expect($ticket->fresh()->approved_by)->toBe($x->id);
});
