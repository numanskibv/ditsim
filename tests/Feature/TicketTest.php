<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CurrentStudent;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\actingAs;

it('generates sequential ticket numbers per type and year', function () {
    $first = Ticket::factory()->create(['type' => TicketType::Incident]);
    $second = Ticket::factory()->create(['type' => TicketType::Incident]);
    $serviceRequest = Ticket::factory()->create(['type' => TicketType::ServiceRequest]);

    expect($first->number)->toBe('INC-2026-0001')
        ->and($second->number)->toBe('INC-2026-0002')
        ->and($serviceRequest->number)->toBe('SR-2026-0001');
});

it('derives sla_minutes from the priority', function () {
    expect(Ticket::factory()->priority(TicketPriority::P1)->create()->sla_minutes)->toBe(60)
        ->and(Ticket::factory()->priority(TicketPriority::P2)->create()->sla_minutes)->toBe(240)
        ->and(Ticket::factory()->priority(TicketPriority::P3)->create()->sla_minutes)->toBe(480);
});

it('flags a ticket closed after its deadline as outside SLA', function () {
    $ticket = Ticket::factory()->priority(TicketPriority::P1)->create();
    $ticket->forceFill([
        'status' => TicketStatus::Afgesloten,
        'closed_at' => $ticket->created_at->addMinutes(120),
    ])->save();

    expect($ticket->isWithinSla())->toBeFalse()
        ->and($ticket->slaStatusLabel())->toBe('Buiten SLA');
});

it('flags a ticket closed before its deadline as within SLA', function () {
    $ticket = Ticket::factory()->priority(TicketPriority::P2)->create();
    $ticket->forceFill([
        'status' => TicketStatus::Afgesloten,
        'closed_at' => $ticket->created_at->addMinutes(120),
    ])->save();

    expect($ticket->isWithinSla())->toBeTrue()
        ->and($ticket->slaStatusLabel())->toBe('Binnen SLA');
});

it('only allows closing with a distinct checker (four-eyes)', function () {
    $executor = User::factory()->technicus()->create();
    $other = User::factory()->technicus()->create();

    expect(Ticket::factory()->assignedTo($executor)->create()->canBeClosed())->toBeFalse()
        ->and(Ticket::factory()->assignedTo($executor)->checkedBy($executor)->create()->canBeClosed())->toBeFalse()
        ->and(Ticket::factory()->assignedTo($executor)->checkedBy($other)->create()->canBeClosed())->toBeTrue();
});

it('throws when closing a ticket without four-eyes', function () {
    $executor = User::factory()->technicus()->create();
    $ticket = Ticket::factory()->assignedTo($executor)->checkedBy($executor)->create();

    $ticket->close();
})->throws(DomainException::class);

it('blocks closing in the UI without a checker and shows a message', function () {
    $executor = User::factory()->technicus()->create();
    $ticket = Ticket::factory()->assignedTo($executor)->create(['status' => TicketStatus::WachtenOpControle]);

    actingAs($executor);
    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->call('closeTicket')
        ->assertHasErrors('close');

    expect($ticket->fresh()->status)->toBe(TicketStatus::WachtenOpControle)
        ->and($ticket->fresh()->closed_at)->toBeNull();
});

it('closes a ticket in the UI when a distinct checker is set', function () {
    $executor = User::factory()->technicus()->create();
    $checker = User::factory()->technicus()->create();
    $ticket = Ticket::factory()->assignedTo($executor)->checkedBy($checker)->create([
        'status' => TicketStatus::WachtenOpControle,
    ]);

    actingAs($executor);
    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->call('closeTicket')
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Afgesloten)
        ->and($ticket->fresh()->closed_at)->not->toBeNull();
});

it('lets a leidinggevende sign off a ticket but forbids a technicus', function () {
    $manager = User::factory()->leidinggevende()->create();
    $student = User::factory()->technicus()->create();
    $technicus = User::factory()->technicus()->create();

    actingAs($manager);
    // A shared role must act within a chosen student's world.
    app(CurrentStudent::class)->setActive($student->id);
    $ticket = Ticket::factory()->create();

    Livewire::test('pages::tickets.show', ['ticket' => $ticket])
        ->call('approve')
        ->assertHasNoErrors();

    expect($ticket->fresh()->approved_by)->toBe($manager->id)
        ->and($ticket->fresh()->approved_at)->not->toBeNull();

    $other = Ticket::factory()->create();
    actingAs($technicus);
    Livewire::test('pages::tickets.show', ['ticket' => $other])
        ->call('approve')
        ->assertForbidden();

    expect($other->fresh()->approved_by)->toBeNull();
});

it('advances the ticket through the workflow', function () {
    $executor = User::factory()->technicus()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    actingAs($executor);
    $component = Livewire::test('pages::tickets.show', ['ticket' => $ticket]);

    $component->call('advanceStatus');
    expect($ticket->fresh()->status)->toBe(TicketStatus::InBehandeling);

    $component->call('advanceStatus');
    expect($ticket->fresh()->status)->toBe(TicketStatus::WachtenOpControle);
});

it('lets a klant create a ticket through the form', function () {
    $klant = User::factory()->klant()->create();
    $student = User::factory()->technicus()->create();

    actingAs($klant);
    // A shared role files the report inside a chosen student's world.
    app(CurrentStudent::class)->setActive($student->id);
    Livewire::test('tickets.ticket-form')
        ->call('openCreate')
        ->set('title', 'Storing melden')
        ->set('type', TicketType::Incident->value)
        ->set('priority', TicketPriority::P1->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('ticket-saved');

    $ticket = Ticket::where('title', 'Storing melden')->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->number)->toBe('INC-2026-0001')
        ->and($ticket->created_by)->toBe($klant->id)
        ->and($ticket->sla_minutes)->toBe(60);
});

it('shows tickets with an SLA badge on the index and filters by status', function () {
    $technicus = User::factory()->technicus()->create();
    $late = Ticket::factory()->priority(TicketPriority::P1)->create(['title' => 'TeLaatTicket']);
    $late->forceFill([
        'status' => TicketStatus::Afgesloten,
        'closed_at' => $late->created_at->addMinutes(200),
    ])->save();
    Ticket::factory()->create(['title' => 'OpenTicket', 'status' => TicketStatus::Open]);

    actingAs($technicus)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('TeLaatTicket')
        ->assertSee('Buiten SLA');

    Livewire::test('pages::tickets.index')
        ->set('statusFilter', TicketStatus::Open->value)
        ->assertSee('OpenTicket')
        ->assertDontSee('TeLaatTicket');
});

it('seeds demo tickets including an SLA breach and a four-eyes closed ticket', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Ticket::count())->toBeGreaterThanOrEqual(3);

    $breached = Ticket::where('number', 'SR-2026-0001')->first();
    expect($breached->isWithinSla())->toBeFalse();

    $closed = Ticket::where('number', 'INC-2026-0002')->first();
    expect($closed->isWithinSla())->toBeTrue()
        ->and($closed->canBeClosed())->toBeTrue()
        ->and($closed->approved_by)->not->toBeNull();
});
