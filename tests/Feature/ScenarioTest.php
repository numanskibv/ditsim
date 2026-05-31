<?php

use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Jobs\ApplyScenarioAction;
use App\Models\Device;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

it('denies a non-docent access to the scenario panel', function () {
    actingAs(User::factory()->technicus()->create())
        ->get(route('scenarios.index'))
        ->assertForbidden();
});

it('lets a docent open the scenario panel', function () {
    actingAs(User::factory()->docent()->create())
        ->get(route('scenarios.index'))
        ->assertOk()
        ->assertSee('Scenariopaneel');
});

it('lets a docent force a device status that broadcasts to the dashboard', function () {
    Event::fake([DeviceStatusChanged::class]);

    $docent = User::factory()->docent()->create();
    $device = Device::factory()->create(['status' => DeviceStatus::Actief]);

    actingAs($docent);
    Livewire::test('pages::scenarios.index')
        ->set('manualDeviceId', $device->id)
        ->set('manualStatus', DeviceStatus::Storing->value)
        ->call('setManualStatus')
        ->assertHasNoErrors();

    expect($device->fresh()->status)->toBe(DeviceStatus::Storing);
    Event::assertDispatched(DeviceStatusChanged::class, fn (DeviceStatusChanged $event) => $event->device->is($device));
});

it('lets a docent save a scenario with actions', function () {
    $docent = User::factory()->docent()->create();
    $device = Device::factory()->create();

    actingAs($docent);
    Livewire::test('pages::scenarios.index')
        ->set('name', 'Koelunit storing')
        ->set('description', 'Demo')
        ->set('actions', [
            ['delay' => 30, 'device_id' => $device->id, 'status' => DeviceStatus::Storing->value],
        ])
        ->call('saveScenario')
        ->assertHasNoErrors();

    $scenario = Scenario::first();

    expect($scenario->name)->toBe('Koelunit storing')
        ->and($scenario->actions)->toHaveCount(1)
        ->and($scenario->created_by)->toBe($docent->id);
});

it('queues each scenario action as a delayed job at the right time', function () {
    $this->freezeTime();
    Queue::fake();

    $first = Device::factory()->create();
    $second = Device::factory()->create();

    $scenario = Scenario::create([
        'name' => 'Cascade',
        'actions' => [
            ['delay' => 30, 'device_id' => $first->id, 'status' => DeviceStatus::Storing->value],
            ['delay' => 60, 'device_id' => $second->id, 'status' => DeviceStatus::Waarschuwing->value],
        ],
    ]);

    $scenario->start();

    Queue::assertPushed(ApplyScenarioAction::class, 2);

    Queue::assertPushed(ApplyScenarioAction::class, fn (ApplyScenarioAction $job): bool => $job->deviceId === $first->id
        && $job->status === DeviceStatus::Storing
        && $job->delay->equalTo(now()->addSeconds(30)));

    Queue::assertPushed(ApplyScenarioAction::class, fn (ApplyScenarioAction $job): bool => $job->deviceId === $second->id
        && $job->status === DeviceStatus::Waarschuwing
        && $job->delay->equalTo(now()->addSeconds(60)));
});

it('applies the status when the scenario action job runs', function () {
    $device = Device::factory()->create(['status' => DeviceStatus::Actief]);

    (new ApplyScenarioAction($device->id, DeviceStatus::Storing))->handle();

    expect($device->fresh()->status)->toBe(DeviceStatus::Storing);
});
