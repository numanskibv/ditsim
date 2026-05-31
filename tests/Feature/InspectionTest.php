<?php

use App\Enums\DeviceStatus;
use App\Enums\InspectionStatus;
use App\Models\Device;
use App\Models\InspectionReport;
use App\Models\Rack;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('saves an inspection report covering every control point', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    Livewire::test('pages::inspections.index')
        ->call('save')
        ->assertHasNoErrors();

    $report = InspectionReport::first();

    expect($report->inspector_id)->toBe($technicus->id)
        ->and($report->items)->toHaveCount(count(InspectionReport::CONTROL_POINTS));
});

it('pushes a linked device into storing for an "actie" finding', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create();
    $device = Device::factory()->for($rack)->create(['status' => DeviceStatus::Actief]);

    actingAs($technicus);
    Livewire::test('pages::inspections.index')
        ->set('items.koeling.status', InspectionStatus::Actie->value)
        ->set('items.koeling.device_id', $device->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($device->fresh()->status)->toBe(DeviceStatus::Storing);
});

it('pushes a linked device into waarschuwing for an "afwijking" finding', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create();
    $device = Device::factory()->for($rack)->create(['status' => DeviceStatus::Actief]);

    actingAs($technicus);
    Livewire::test('pages::inspections.index')
        ->set('items.stroom.status', InspectionStatus::Afwijking->value)
        ->set('items.stroom.device_id', $device->id)
        ->call('save');

    expect($device->fresh()->status)->toBe(DeviceStatus::Waarschuwing);
});

it('leaves devices untouched for an OK finding', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create();
    $device = Device::factory()->for($rack)->create(['status' => DeviceStatus::Actief]);

    actingAs($technicus);
    Livewire::test('pages::inspections.index')
        ->set('items.koeling.device_id', $device->id)
        ->call('save');

    expect($device->fresh()->status)->toBe(DeviceStatus::Actief);
});

it('renders the fixed control points on the checklist', function () {
    actingAs(User::factory()->technicus()->create())
        ->get(route('inspections.index'))
        ->assertOk()
        ->assertSee('Koeling')
        ->assertSee('Stroom (UPS/PDU)')
        ->assertSee('Brandveiligheid')
        ->assertSee('Security/toegang')
        ->assertSee('Racks/kabelmanagement');
});

it('forbids a klant from saving an inspection report', function () {
    actingAs(User::factory()->klant()->create());

    Livewire::test('pages::inspections.index')
        ->call('save')
        ->assertForbidden();
});
