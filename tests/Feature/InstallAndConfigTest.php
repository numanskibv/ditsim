<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

it('seeds an empty database once and is safe to run again', function () {
    expect(User::query()->count())->toBe(0);

    Artisan::call('app:install');
    $afterFirst = User::query()->count();

    expect($afterFirst)->toBeGreaterThan(0);

    // Running again must not duplicate the seed data.
    Artisan::call('app:install');

    expect(User::query()->count())->toBe($afterFirst);
});

it('uses the configured default password for seeded accounts', function () {
    config(['datacenter.default_password' => 'sterk-geheim-123']);

    $this->seed(DatabaseSeeder::class);

    $docent = User::where('email', 'docent@datacenter-sim.test')->first();

    expect(Hash::check('sterk-geheim-123', $docent->password))->toBeTrue();
});

it('creates students with the configured default password from the docent screen', function () {
    config(['datacenter.default_password' => 'klas-2026']);

    actingAs(User::factory()->docent()->create());
    Livewire::test('pages::students.index')
        ->set('newName', 'Nieuwe Student')
        ->set('newNumber', 'S100002')
        ->set('newEmail', 'nieuwe@datacenter-sim.test')
        ->call('createStudent')
        ->assertHasNoErrors();

    $student = User::where('email', 'nieuwe@datacenter-sim.test')->first();

    expect(Hash::check('klas-2026', $student->password))->toBeTrue();
});
