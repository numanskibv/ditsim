<?php

use App\Models\Device;
use App\Models\Rack;
use App\Models\Scenario;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ScenarioLibrarySeeder;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

it('seeds at least 15 assignable provisioning scenarios', function () {
    $this->seed(ScenarioLibrarySeeder::class);

    $provisioning = Scenario::all()->filter->isProvisioning();

    expect($provisioning->count())->toBeGreaterThanOrEqual(15);
});

it('is idempotent: re-seeding does not duplicate scenarios', function () {
    $this->seed(ScenarioLibrarySeeder::class);
    $count = Scenario::count();

    $this->seed(ScenarioLibrarySeeder::class);

    expect(Scenario::count())->toBe($count);
});

it('clears all world data via the command but keeps users and scenarios', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Device::withoutGlobalScopes()->count())->toBeGreaterThan(0);

    $users = User::count();
    $scenarios = Scenario::count();

    Artisan::call('simulate:clear');

    expect(Device::withoutGlobalScopes()->count())->toBe(0)
        ->and(Rack::withoutGlobalScopes()->count())->toBe(0)
        ->and(Ticket::withoutGlobalScopes()->count())->toBe(0)
        ->and(User::count())->toBe($users)
        ->and(Scenario::count())->toBe($scenarios);
});

it('lets a docent remove demo data from the screen', function () {
    $this->seed(DatabaseSeeder::class);

    actingAs(User::where('email', 'docent@datacenter-sim.test')->first());

    Livewire::test('pages::students.index')
        ->call('clearDemoData')
        ->assertHasNoErrors();

    expect(Device::withoutGlobalScopes()->count())->toBe(0)
        ->and(Scenario::count())->toBeGreaterThan(0);
});

it('re-imports the demo data without duplicating it', function () {
    $this->seed(DatabaseSeeder::class);

    $scenariosBefore = Scenario::count();

    // Wipe the demo, then import it back.
    Artisan::call('simulate:clear');
    expect(Device::withoutGlobalScopes()->count())->toBe(0);

    Artisan::call('simulate:import-demo');

    $devicesAfterFirst = Device::withoutGlobalScopes()->count();

    expect($devicesAfterFirst)->toBe(4)
        ->and(Rack::withoutGlobalScopes()->where('name', 'R03')->count())->toBe(1)
        ->and(Scenario::count())->toBe($scenariosBefore);

    // Importing again must not duplicate the demo world or scenarios.
    Artisan::call('simulate:import-demo');

    expect(Device::withoutGlobalScopes()->count())->toBe($devicesAfterFirst)
        ->and(Rack::withoutGlobalScopes()->where('name', 'R03')->count())->toBe(1)
        ->and(Scenario::count())->toBe($scenariosBefore);
});

it('lets a docent import demo data from the screen', function () {
    $this->seed(DatabaseSeeder::class);
    Artisan::call('simulate:clear');

    actingAs(User::where('email', 'docent@datacenter-sim.test')->first());

    Livewire::test('pages::students.index')
        ->call('importDemoData')
        ->assertHasNoErrors();

    expect(Device::withoutGlobalScopes()->count())->toBe(4);
});
