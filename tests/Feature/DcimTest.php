<?php

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\Rack;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('automatically fills last_changed_by with the authenticated user on create', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $device = Device::factory()->create();

    expect($device->last_changed_by)->toBe($technicus->id)
        ->and($device->last_changed_at)->not->toBeNull();
});

it('moves the audit stamp to whoever changes the device', function () {
    $author = User::factory()->technicus()->create();
    $editor = User::factory()->technicus()->create();

    actingAs($author);
    $device = Device::factory()->create(['status' => DeviceStatus::Actief]);
    expect($device->last_changed_by)->toBe($author->id);

    actingAs($editor);
    $device->update(['status' => DeviceStatus::Storing]);

    expect($device->fresh()->last_changed_by)->toBe($editor->id);
});

it('lets a technicus add a device through the form', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create(['height_u' => 42]);

    actingAs($technicus);
    Livewire::test('dcim.device-form')
        ->call('openCreate', $rack->id)
        ->set('name', 'srv-test')
        ->set('type', DeviceType::Server->value)
        ->set('status', DeviceStatus::Actief->value)
        ->set('u_start', 1)
        ->set('u_end', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('device-saved');

    $device = Device::where('name', 'srv-test')->first();

    expect($device)->not->toBeNull()
        ->and($device->rack_id)->toBe($rack->id)
        ->and($device->last_changed_by)->toBe($technicus->id);
});

it('saves a new device with the default type and status without touching them', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create(['height_u' => 42]);

    actingAs($technicus);
    Livewire::test('dcim.device-form')
        ->call('openCreate', $rack->id)
        ->set('name', 'srv-default')
        ->set('u_start', 1)
        ->set('u_end', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('device-saved');

    $device = Device::where('name', 'srv-default')->first();

    expect($device)->not->toBeNull()
        ->and($device->type)->toBe(DeviceType::Server)
        ->and($device->status)->toBe(DeviceStatus::Actief);
});

it('moves a device to another rack through the form', function () {
    $technicus = User::factory()->technicus()->create();
    $from = Rack::factory()->create();
    $to = Rack::factory()->create();

    actingAs($technicus);
    $device = Device::factory()->for($from)->atPosition(1, 1)->create();

    Livewire::test('dcim.device-form')
        ->call('openEdit', $device->id)
        ->set('rack_id', $to->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($device->fresh()->rack_id)->toBe($to->id);
});

it('lets a technicus build a rack in their own world through the form', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    Livewire::test('dcim.rack-form')
        ->call('openCreate')
        ->set('name', 'R09')
        ->set('location', 'DC-Utrecht')
        ->set('height_u', 42)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('rack-saved');

    $rack = Rack::where('name', 'R09')->first();

    expect($rack)->not->toBeNull()
        ->and($rack->location)->toBe('DC-Utrecht')
        ->and($rack->height_u)->toBe(42)
        ->and($rack->student_id)->toBe($technicus->id);
});

it('forbids a klant from creating a rack', function () {
    $klant = User::factory()->klant()->create();

    actingAs($klant);
    Livewire::test('dcim.rack-form')
        ->call('openCreate')
        ->assertForbidden();
});

it('lets a technicus delete a rack and cascades its devices', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $device = Device::factory()->for($rack)->atPosition(1, 2)->create();

    Livewire::test('dcim.rack-form')
        ->call('delete', $rack->id)
        ->assertHasNoErrors()
        ->assertDispatched('rack-saved');

    expect(Rack::find($rack->id))->toBeNull()
        ->and(Device::find($device->id))->toBeNull();
});

it('forbids a klant from deleting a rack', function () {
    $klant = User::factory()->klant()->create();
    $rack = Rack::factory()->create();

    actingAs($klant);
    Livewire::test('dcim.rack-form')
        ->call('delete', $rack->id)
        ->assertForbidden();

    expect(Rack::find($rack->id))->not->toBeNull();
});

it('forbids a klant from opening the device form', function () {
    $klant = User::factory()->klant()->create();
    $rack = Rack::factory()->create();

    actingAs($klant);
    Livewire::test('dcim.device-form')
        ->call('openCreate', $rack->id)
        ->assertForbidden();
});

it('rejects a device that overlaps another in the same rack', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create(['height_u' => 42]);
    Device::factory()->for($rack)->atPosition(1, 4)->create();

    actingAs($technicus);
    Livewire::test('dcim.device-form')
        ->call('openCreate', $rack->id)
        ->set('name', 'overlap')
        ->set('type', DeviceType::Server->value)
        ->set('status', DeviceStatus::Actief->value)
        ->set('u_start', 3)
        ->set('u_end', 5)
        ->call('save')
        ->assertHasErrors('u_start');
});

it('rejects a device that exceeds the rack height', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create(['height_u' => 10]);

    actingAs($technicus);
    Livewire::test('dcim.device-form')
        ->call('openCreate', $rack->id)
        ->set('name', 'too-tall')
        ->set('type', DeviceType::Server->value)
        ->set('status', DeviceStatus::Actief->value)
        ->set('u_start', 9)
        ->set('u_end', 12)
        ->call('save')
        ->assertHasErrors('u_end');
});

it('shows a rack with its colored devices on the overview', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create(['name' => 'R03', 'location' => 'DC-Utrecht']);
    Device::factory()->for($rack)->atPosition(1, 2)->create([
        'name' => 'medicloud-app01',
        'status' => DeviceStatus::Actief,
    ]);

    actingAs($technicus)
        ->get(route('dcim.racks'))
        ->assertOk()
        ->assertSee('R03')
        ->assertSee('DC-Utrecht')
        ->assertSee('medicloud-app01')
        ->assertSee('bg-green-500/80', false);
});

it('reflects the editor in the last-changed indicator after an edit', function () {
    $technicus = User::factory()->technicus()->create();
    $rack = Rack::factory()->create(['name' => 'R03']);
    $device = Device::factory()->for($rack)->atPosition(1, 2)->create(['status' => DeviceStatus::Actief]);

    actingAs($technicus);
    $device->update(['status' => DeviceStatus::Storing]);

    actingAs($technicus)
        ->get(route('dcim.racks'))
        ->assertOk()
        ->assertSee('Laatste wijziging')
        ->assertSee($technicus->name);
});

it('seeds rack R03 in DC-Utrecht with the MediCloud devices', function () {
    $this->seed(DatabaseSeeder::class);

    $rack = Rack::where('name', 'R03')->first();

    expect($rack)->not->toBeNull()
        ->and($rack->location)->toBe('DC-Utrecht')
        ->and($rack->devices)->toHaveCount(4);

    $switch = $rack->devices->firstWhere('name', 'medicloud-sw01');

    expect($switch->status)->toBe(DeviceStatus::Offline)
        ->and($switch->type)->toBe(DeviceType::Switch)
        ->and($switch->owner->name)->toBe('MediCloud BV');
});
