<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\post;

it('makes the very first registration a docent', function () {
    expect(User::query()->count())->toBe(0);

    post(route('register.store'), [
        'name' => 'Eerste Docent',
        'email' => 'eerste@school.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(User::where('email', 'eerste@school.test')->first()->role)->toBe(Role::Docent);
});

it('makes later registrations a regular klant', function () {
    User::factory()->docent()->create();

    post(route('register.store'), [
        'name' => 'Tweede Gebruiker',
        'email' => 'tweede@school.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'tweede@school.test')->first()->role)->toBe(Role::Klant);
});

it('app:install starts empty when SEED_DEMO is off, so the first user can claim docent', function () {
    config(['datacenter.seed_demo' => false]);

    Artisan::call('app:install');

    expect(User::query()->count())->toBe(0);
});

it('app:install seeds the demo by default', function () {
    config(['datacenter.seed_demo' => true]);

    Artisan::call('app:install');

    expect(User::query()->count())->toBeGreaterThan(0);
});
