<?php

use App\Models\User;
use App\Support\CurrentStudent;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows an overview banner to a shared role without a selected world', function () {
    actingAs(User::factory()->leidinggevende()->create());

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Overzichtsmodus');
});

it('shows the active world to a shared role that selected a student', function () {
    $student = User::factory()->technicus()->create(['name' => 'Wereld Eigenaar']);

    actingAs(User::factory()->leidinggevende()->create());
    app(CurrentStudent::class)->setActive($student->id);

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Je werkt in de omgeving van')
        ->assertSee('Wereld Eigenaar');
});

it('shows no world banner to a technicus in their own world', function () {
    actingAs(User::factory()->technicus()->create());

    get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Overzichtsmodus')
        ->assertDontSee('Je werkt in de omgeving van');
});
