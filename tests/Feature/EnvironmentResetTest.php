<?php

use App\Models\Device;
use App\Models\Rack;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

it('wipes all data and reseeds the demo via the reset command', function () {
    $this->seed(DatabaseSeeder::class);

    // Add extra data on top of the seed.
    $extra = User::factory()->technicus()->create(['email' => 'extra@datacenter-sim.test']);
    actingAs($extra);
    Device::factory()->create(['name' => 'extra-device']);

    Artisan::call('simulate:reset');

    // The extra student and their data are gone; the seed is back.
    expect(User::where('email', 'extra@datacenter-sim.test')->exists())->toBeFalse()
        ->and(User::where('email', 'docent@datacenter-sim.test')->exists())->toBeTrue()
        ->and(User::where('email', 'technicus@datacenter-sim.test')->exists())->toBeTrue()
        ->and(Device::withoutGlobalScopes()->where('name', 'extra-device')->exists())->toBeFalse()
        ->and(Rack::withoutGlobalScopes()->where('name', 'R03')->exists())->toBeTrue();
});

it('lets a docent factory-reset from the UI after typing RESET', function () {
    $this->seed(DatabaseSeeder::class);
    $docent = User::where('email', 'docent@datacenter-sim.test')->first();

    $extra = User::factory()->technicus()->create(['email' => 'weg@datacenter-sim.test']);

    actingAs($docent);
    Livewire::test('pages::students.index')
        ->set('resetConfirmation', 'RESET')
        ->call('factoryReset')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(User::where('email', 'weg@datacenter-sim.test')->exists())->toBeFalse()
        ->and(User::where('email', 'docent@datacenter-sim.test')->exists())->toBeTrue();
});

it('refuses the factory reset without the typed confirmation', function () {
    $this->seed(DatabaseSeeder::class);
    $docent = User::where('email', 'docent@datacenter-sim.test')->first();
    $extra = User::factory()->technicus()->create(['email' => 'blijft@datacenter-sim.test']);

    actingAs($docent);
    Livewire::test('pages::students.index')
        ->set('resetConfirmation', 'reset')
        ->call('factoryReset')
        ->assertHasErrors('resetConfirmation');

    // Nothing was wiped.
    expect(User::where('email', 'blijft@datacenter-sim.test')->exists())->toBeTrue();
});

it('forbids a non-docent from factory-resetting', function () {
    actingAs(User::factory()->technicus()->create());

    Livewire::test('pages::students.index')
        ->assertForbidden();
});
