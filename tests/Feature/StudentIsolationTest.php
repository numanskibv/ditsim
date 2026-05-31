<?php

use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Models\Device;
use App\Models\Rack;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CurrentStudent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

it('isolates one student\'s devices from another\'s', function () {
    $sanne = User::factory()->technicus()->create();
    $sven = User::factory()->technicus()->create();

    actingAs($sanne);
    $rackA = Rack::factory()->create(['name' => 'R-A']);
    Device::factory()->for($rackA)->create(['name' => 'sanne-srv']);

    actingAs($sven);
    $rackB = Rack::factory()->create(['name' => 'R-B']);
    Device::factory()->for($rackB)->create(['name' => 'sven-srv']);

    // Sven only sees his own world.
    expect(Device::pluck('name')->all())->toBe(['sven-srv'])
        ->and(Rack::pluck('name')->all())->toBe(['R-B']);

    // Sanne only sees hers.
    actingAs($sanne);
    expect(Device::pluck('name')->all())->toBe(['sanne-srv'])
        ->and(Rack::pluck('name')->all())->toBe(['R-A']);
});

it('stamps new state with the acting student\'s world', function () {
    $sanne = User::factory()->technicus()->create();

    actingAs($sanne);
    $device = Device::factory()->create();

    expect($device->student_id)->toBe($sanne->id);
});

it('lets a shared role see everything until it selects a student', function () {
    $sanne = User::factory()->technicus()->create();
    $sven = User::factory()->technicus()->create();

    actingAs($sanne);
    Device::factory()->create(['name' => 'sanne-srv']);
    actingAs($sven);
    Device::factory()->create(['name' => 'sven-srv']);

    $docent = User::factory()->docent()->create();
    actingAs($docent);
    $resolver = app(CurrentStudent::class);

    // No selection → overview of all worlds.
    expect(Device::count())->toBe(2);

    // Select one student → only that world.
    $resolver->setActive($sanne->id);
    expect(Device::pluck('name')->all())->toBe(['sanne-srv']);
});

it('broadcasts a device status change on its own student world channel', function () {
    $sanne = User::factory()->technicus()->create();

    actingAs($sanne);
    $device = Device::factory()->create(['status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20]);

    $event = new DeviceStatusChanged($device);

    expect($event->broadcastOn()[0]->name)->toBe('monitoring.'.$sanne->id);
});

it('only ticks the targeted student world from the command', function () {
    $sanne = User::factory()->technicus()->create();
    $sven = User::factory()->technicus()->create();

    actingAs($sanne);
    $sanneDevice = Device::factory()->create(['status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20, 'metric_trend' => 10]);
    actingAs($sven);
    $svenDevice = Device::factory()->create(['status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20, 'metric_trend' => 10]);

    Artisan::call('simulate:tick', ['--student' => $sanne->id]);

    expect($sanneDevice->fresh()->temp)->toBe(50)
        ->and($svenDevice->fresh()->temp)->toBe(40);
});

it('runs a UI tick scoped to the acting student only', function () {
    $sanne = User::factory()->technicus()->create();
    $sven = User::factory()->technicus()->create();

    actingAs($sanne);
    $sanneDevice = Device::factory()->create(['status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20, 'metric_trend' => 10]);
    actingAs($sven);
    $svenDevice = Device::factory()->create(['status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20, 'metric_trend' => 10]);

    actingAs($sanne);
    Livewire::test('pages::monitoring.index')
        ->call('runTick')
        ->assertHasNoErrors();

    expect($sanneDevice->fresh()->temp)->toBe(50)
        ->and($svenDevice->fresh()->temp)->toBe(40);
});

it('starts a freshly added student with an empty world while the demo world stays populated', function () {
    $this->seed(DatabaseSeeder::class);

    $tessa = User::where('email', 'technicus@datacenter-sim.test')->first();
    $sanne = User::where('email', 'sanne@datacenter-sim.test')->first();

    // Tessa owns the seeded demo world.
    actingAs($tessa);
    expect(Device::count())->toBe(4)
        ->and(Ticket::count())->toBeGreaterThanOrEqual(3);

    // A freshly seeded student starts empty.
    actingAs($sanne);
    expect(Device::count())->toBe(0)
        ->and(Rack::count())->toBe(0)
        ->and(Ticket::count())->toBe(0);
});
