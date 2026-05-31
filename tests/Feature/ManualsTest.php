<?php

use App\Models\User;
use App\Support\Manuals;

use function Pest\Laravel\actingAs;

it('lists the role manuals with friendly labels', function () {
    $slugs = app(Manuals::class)->all()->pluck('slug');

    expect($slugs)->toContain('docent', 'technicus', 'leidinggevende', 'klant')
        ->and(app(Manuals::class)->label('technicus'))->toBe('Technicus');
});

it('renders a manual to HTML including tables', function () {
    $html = app(Manuals::class)->html('docent');

    expect($html)->toContain('<h1>')
        ->and($html)->toContain('<table>');
});

it('rejects unsafe slugs (path traversal)', function () {
    expect(app(Manuals::class)->exists('../../.env'))->toBeFalse()
        ->and(app(Manuals::class)->html('../../.env'))->toBeNull();
});

it('lets a docent open the manuals page', function () {
    actingAs(User::factory()->docent()->create());

    Livewire::test('pages::manuals.index')
        ->assertSee('Handleidingen')
        ->assertSee('Technicus')
        ->call('select', 'klant')
        ->assertSet('slug', 'klant');
});

it('forbids a non-docent from the manuals page', function () {
    actingAs(User::factory()->technicus()->create())
        ->get(route('manuals.index'))
        ->assertForbidden();
});

it('serves a clean printable version to the docent', function () {
    actingAs(User::factory()->docent()->create())
        ->get(route('manuals.print', ['slug' => 'technicus']))
        ->assertOk()
        ->assertSee('Print / Opslaan als PDF')
        ->assertSee('Technicus — Handleiding');
});

it('returns 404 for an unknown manual slug', function () {
    actingAs(User::factory()->docent()->create())
        ->get(route('manuals.print', ['slug' => 'bestaat-niet']))
        ->assertNotFound();
});

it('forbids a non-docent from the print version', function () {
    actingAs(User::factory()->klant()->create())
        ->get(route('manuals.print', ['slug' => 'technicus']))
        ->assertForbidden();
});
