<?php

use App\Enums\CableMedium;
use App\Enums\DeviceType;
use App\Models\Cable;
use App\Models\Device;
use App\Models\Rack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('lets a technicus patch a numbered cable between two ports', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $switch = Device::factory()->for($rack)->create(['name' => 'sw01', 'port_count' => 24]);
    $patch = Device::factory()->for($rack)->create(['name' => 'patch01', 'type' => DeviceType::Patchpaneel, 'port_count' => 24]);

    Livewire::test('dcim.cable-form')
        ->call('openCreate')
        ->set('label', 'K-001')
        ->set('medium', CableMedium::Utp->value)
        ->set('from_device_id', $switch->id)
        ->set('from_port', 3)
        ->set('to_device_id', $patch->id)
        ->set('to_port', 1)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('cable-saved');

    $cable = Cable::where('label', 'K-001')->first();

    expect($cable)->not->toBeNull()
        ->and($cable->student_id)->toBe($technicus->id)
        ->and($cable->from_device_id)->toBe($switch->id)
        ->and($cable->to_port)->toBe(1)
        ->and($cable->last_changed_by)->toBe($technicus->id);
});

it('rejects a port beyond the device port count', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create(['port_count' => 4]);
    $b = Device::factory()->for($rack)->create(['port_count' => 4]);

    Livewire::test('dcim.cable-form')
        ->call('openCreate')
        ->set('label', 'K-002')
        ->set('from_device_id', $a->id)
        ->set('from_port', 9)
        ->set('to_device_id', $b->id)
        ->set('to_port', 1)
        ->call('save')
        ->assertHasErrors('from_port');

    expect(Cable::count())->toBe(0);
});

it('rejects a port that is already occupied by another cable', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create(['port_count' => 24]);
    $b = Device::factory()->for($rack)->create(['port_count' => 24]);
    Cable::factory()->between($a, 1, $b, 1)->create(['label' => 'K-existing']);

    Livewire::test('dcim.cable-form')
        ->call('openCreate')
        ->set('label', 'K-003')
        ->set('from_device_id', $a->id)
        ->set('from_port', 1)
        ->set('to_device_id', $b->id)
        ->set('to_port', 5)
        ->call('save')
        ->assertHasErrors('from_port');
});

it('rejects a cable from a port to the same port', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create(['port_count' => 24]);

    Livewire::test('dcim.cable-form')
        ->call('openCreate')
        ->set('label', 'K-004')
        ->set('from_device_id', $a->id)
        ->set('from_port', 2)
        ->set('to_device_id', $a->id)
        ->set('to_port', 2)
        ->call('save')
        ->assertHasErrors('to_port');
});

it('forbids a klant from creating a cable', function () {
    $klant = User::factory()->klant()->create();

    actingAs($klant);
    Livewire::test('dcim.cable-form')
        ->call('openCreate')
        ->assertForbidden();
});

it('lets a technicus delete a cable', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create(['port_count' => 4]);
    $b = Device::factory()->for($rack)->create(['port_count' => 4]);
    $cable = Cable::factory()->between($a, 1, $b, 1)->create();

    Livewire::test('dcim.cable-form')
        ->call('delete', $cable->id)
        ->assertDispatched('cable-saved');

    expect(Cable::find($cable->id))->toBeNull();
});

it('shows the cable schedule on the cabling page', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create(['name' => 'sw01', 'port_count' => 24]);
    $b = Device::factory()->for($rack)->create(['name' => 'patch01', 'port_count' => 24]);
    Cable::factory()->between($a, 3, $b, 1)->create(['label' => 'K-001', 'medium' => CableMedium::Utp]);

    actingAs($technicus)
        ->get(route('dcim.cabling'))
        ->assertOk()
        ->assertSee('K-001')
        ->assertSee('sw01')
        ->assertSee('patch01');
});

it('opens the cable form pre-filled when two ports are patched', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create(['port_count' => 24]);
    $b = Device::factory()->for($rack)->create(['port_count' => 24]);

    Livewire::test('dcim.cable-form')
        ->call('openFromPorts', $a->id, 5, $b->id, 2)
        ->assertSet('showModal', true)
        ->assertSet('from_device_id', $a->id)
        ->assertSet('from_port', 5)
        ->assertSet('to_device_id', $b->id)
        ->assertSet('to_port', 2);
});

it('forbids a klant from patching ports', function () {
    $klant = User::factory()->klant()->create();
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create();
    $b = Device::factory()->for($rack)->create();

    actingAs($klant);
    Livewire::test('dcim.cable-form')
        ->call('openFromPorts', $a->id, 1, $b->id, 2)
        ->assertForbidden();
});

it('downloads the cable schedule as a PDF', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $rack = Rack::factory()->create();
    $a = Device::factory()->for($rack)->create(['name' => 'sw01', 'port_count' => 24]);
    $b = Device::factory()->for($rack)->create(['name' => 'patch01', 'port_count' => 24]);
    Cable::factory()->between($a, 3, $b, 1)->create(['label' => 'K-001']);

    $response = actingAs($technicus)->get(route('dcim.cabling.pdf'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
