<?php

use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Models\Device;
use App\Models\Rack;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

it('derives status from metrics against the thresholds', function () {
    expect(Device::statusFromMetrics(20, 40))->toBe(DeviceStatus::Actief)
        ->and(Device::statusFromMetrics(20, 65))->toBe(DeviceStatus::Waarschuwing)
        ->and(Device::statusFromMetrics(90, 40))->toBe(DeviceStatus::Waarschuwing)
        ->and(Device::statusFromMetrics(20, 80))->toBe(DeviceStatus::Storing)
        ->and(Device::statusFromMetrics(96, 40))->toBe(DeviceStatus::Storing);
});

it('mutates a trending device temperature on each tick', function () {
    $device = Device::factory()->create([
        'status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20, 'metric_trend' => 10,
    ]);

    Artisan::call('simulate:tick');

    expect($device->fresh()->temp)->toBe(50);
});

it('does not simulate offline devices', function () {
    $device = Device::factory()->create([
        'status' => DeviceStatus::Offline, 'temp' => 0, 'cpu' => 0, 'metric_trend' => 10,
    ]);

    Artisan::call('simulate:tick');

    expect($device->fresh()->status)->toBe(DeviceStatus::Offline)
        ->and($device->fresh()->temp)->toBe(0);
});

it('passes through waarschuwing before storing as a metric climbs (predictive)', function () {
    $device = Device::factory()->create([
        'status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20, 'metric_trend' => 10,
    ]);

    $seen = [];
    for ($i = 0; $i < 6; $i++) {
        Artisan::call('simulate:tick');
        $seen[] = $device->fresh()->status;
    }

    $warningIndex = array_search(DeviceStatus::Waarschuwing, $seen, true);
    $faultIndex = array_search(DeviceStatus::Storing, $seen, true);

    expect($warningIndex)->not->toBeFalse()
        ->and($faultIndex)->not->toBeFalse()
        ->and($warningIndex)->toBeLessThan($faultIndex);
});

it('broadcasts a DeviceStatusChanged event when a device changes status', function () {
    Event::fake([DeviceStatusChanged::class]);

    $device = Device::factory()->create(['status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20]);
    $device->update(['status' => DeviceStatus::Storing, 'temp' => 85]);

    Event::assertDispatched(DeviceStatusChanged::class, fn (DeviceStatusChanged $event) => $event->device->is($device)
        && $event->from === DeviceStatus::Actief);
});

it('records a NOC alert when a device degrades', function () {
    $device = Device::factory()->create(['status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20]);

    $device->update(['status' => DeviceStatus::Storing, 'temp' => 85]);

    expect($device->fresh()->alerts()->count())->toBe(1)
        ->and($device->fresh()->alerts()->first()->to_status)->toBe(DeviceStatus::Storing);
});

it('does not record an alert when a device recovers to actief', function () {
    $device = Device::factory()->create(['status' => DeviceStatus::Storing, 'temp' => 85, 'cpu' => 20]);

    $device->update(['status' => DeviceStatus::Actief, 'temp' => 40]);

    expect($device->fresh()->alerts()->count())->toBe(0);
});

it('renders the monitoring dashboard with devices and the alarm panel', function () {
    $user = User::factory()->technicus()->create();
    $rack = Rack::factory()->create();
    Device::factory()->for($rack)->create([
        'name' => 'mon-srv', 'status' => DeviceStatus::Actief, 'cpu' => 30, 'temp' => 45,
    ]);

    actingAs($user)
        ->get(route('monitoring'))
        ->assertOk()
        ->assertSee('Monitoring')
        ->assertSee('mon-srv')
        ->assertSee('Alarmen');
});

it('lets a technicus run a tick from the UI but forbids a klant', function () {
    $rack = Rack::factory()->create();
    Device::factory()->for($rack)->create([
        'status' => DeviceStatus::Actief, 'temp' => 40, 'cpu' => 20, 'metric_trend' => 10,
    ]);

    actingAs(User::factory()->technicus()->create());
    Livewire::test('pages::monitoring.index')
        ->call('runTick')
        ->assertHasNoErrors();

    actingAs(User::factory()->klant()->create());
    Livewire::test('pages::monitoring.index')
        ->call('runTick')
        ->assertForbidden();
});
