<?php

use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('sends a message stamped with sender and time, visible to the recipient', function () {
    $sender = User::factory()->technicus()->create();
    // The recipient is a shared role (the supervisor/colleague): a student
    // communicates within their own world with a shared instructor, never with
    // another student's isolated world.
    $colleague = User::factory()->leidinggevende()->create();

    actingAs($sender);
    Livewire::test('pages::messages.index')
        ->set('to_id', $colleague->id)
        ->set('body', 'Kun je rack R03 checken?')
        ->call('send')
        ->assertHasNoErrors();

    $message = Message::first();
    expect($message->from_id)->toBe($sender->id)
        ->and($message->to_id)->toBe($colleague->id)
        ->and($message->sent_at)->not->toBeNull();

    actingAs($colleague);
    Livewire::test('pages::messages.index')
        ->assertSee('Kun je rack R03 checken?')
        ->assertSee($sender->name);
});

it('links a message to a ticket and shows it in the ticket timeline', function () {
    $sender = User::factory()->technicus()->create();
    $colleague = User::factory()->technicus()->create();
    $ticket = Ticket::factory()->create();

    actingAs($sender);
    Livewire::test('pages::messages.index')
        ->set('to_id', $colleague->id)
        ->set('ticket_id', $ticket->id)
        ->set('body', 'Statusupdate op dit ticket')
        ->call('send')
        ->assertHasNoErrors();

    expect($ticket->messages()->count())->toBe(1);

    Livewire::test('messages.ticket-thread', ['ticket' => $ticket])
        ->assertSee('Statusupdate op dit ticket');
});

it('posts a message from the ticket thread tied to that ticket', function () {
    $sender = User::factory()->technicus()->create();
    $colleague = User::factory()->technicus()->create();
    $ticket = Ticket::factory()->create();

    actingAs($sender);
    Livewire::test('messages.ticket-thread', ['ticket' => $ticket])
        ->set('to_id', $colleague->id)
        ->set('body', 'Vanuit de ticket-thread')
        ->call('send')
        ->assertHasNoErrors();

    $message = Message::first();
    expect($message->ticket_id)->toBe($ticket->id)
        ->and($message->from_id)->toBe($sender->id);
});

it('does not allow sending a message to yourself', function () {
    $sender = User::factory()->technicus()->create();

    actingAs($sender);
    Livewire::test('pages::messages.index')
        ->set('to_id', $sender->id)
        ->set('body', 'Notitie aan mezelf')
        ->call('send')
        ->assertHasErrors('to_id');
});

it('shows both sent and received messages in the user timeline', function () {
    $user = User::factory()->technicus()->create();
    $other = User::factory()->technicus()->create();

    Message::factory()->create(['from_id' => $user->id, 'to_id' => $other->id, 'body' => 'Verzonden door mij']);
    Message::factory()->create(['from_id' => $other->id, 'to_id' => $user->id, 'body' => 'Ontvangen door mij']);

    actingAs($user);
    Livewire::test('pages::messages.index')
        ->assertSee('Verzonden door mij')
        ->assertSee('Ontvangen door mij');
});
