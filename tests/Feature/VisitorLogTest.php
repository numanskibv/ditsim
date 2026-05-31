<?php

use App\Models\User;
use App\Models\VisitorLog;

use function Pest\Laravel\actingAs;

it('registers a visitor with a check-in time and an open status', function () {
    actingAs(User::factory()->technicus()->create());

    Livewire::test('pages::access.index')
        ->set('visitor_name', 'Alice Bezoeker')
        ->set('reason', 'Onderhoud koeling')
        ->call('register')
        ->assertHasNoErrors();

    $visitor = VisitorLog::first();

    expect($visitor->visitor_name)->toBe('Alice Bezoeker')
        ->and($visitor->checked_in_at)->not->toBeNull()
        ->and($visitor->checked_out_at)->toBeNull()
        ->and($visitor->isOpen())->toBeTrue()
        ->and($visitor->isCompleteEvidence())->toBeFalse();
});

it('checks a visitor out, completing the evidence', function () {
    $visitor = VisitorLog::factory()->create();

    actingAs(User::factory()->technicus()->create());
    Livewire::test('pages::access.index')
        ->call('checkOut', $visitor->id)
        ->assertHasNoErrors();

    $visitor->refresh();

    expect($visitor->checked_out_at)->not->toBeNull()
        ->and($visitor->isOpen())->toBeFalse()
        ->and($visitor->isCompleteEvidence())->toBeTrue();
});

it('only counts as complete evidence when both timestamps are present', function () {
    expect(VisitorLog::factory()->create()->isCompleteEvidence())->toBeFalse()
        ->and(VisitorLog::factory()->checkedOut()->create()->isCompleteEvidence())->toBeTrue();
});

it('visually marks a not-yet-checked-out visitor on the list', function () {
    VisitorLog::factory()->create(['visitor_name' => 'OpenVisitor']);

    actingAs(User::factory()->technicus()->create())
        ->get(route('access.index'))
        ->assertOk()
        ->assertSee('OpenVisitor')
        ->assertSee('Open — nog binnen');
});

it('forbids a klant from registering a visitor', function () {
    actingAs(User::factory()->klant()->create());

    Livewire::test('pages::access.index')
        ->set('visitor_name', 'X')
        ->set('reason', 'Y')
        ->call('register')
        ->assertForbidden();
});
