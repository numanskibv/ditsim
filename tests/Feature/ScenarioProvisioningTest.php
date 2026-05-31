<?php

use App\Enums\DeviceStatus;
use App\Enums\Role;
use App\Jobs\ApplyScenarioAction;
use App\Models\Device;
use App\Models\Rack;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

/**
 * A scenario that builds a full starting world from scratch.
 */
function provisioningScenario(): Scenario
{
    return Scenario::create([
        'name' => 'Startopstelling',
        'description' => 'Bouwt een rack met devices.',
        'actions' => [
            ['delay' => 60, 'device' => 'srv-db', 'status' => DeviceStatus::Storing->value],
        ],
        'blueprint' => [
            'rack' => ['name' => 'R03', 'location' => 'DC-Utrecht', 'height_u' => 42],
            'devices' => [
                ['name' => 'srv-web', 'type' => 'server', 'status' => 'actief', 'u_start' => 1, 'u_end' => 2, 'cpu' => 20, 'temp' => 40, 'metric_trend' => 0],
                ['name' => 'srv-db', 'type' => 'server', 'status' => 'actief', 'u_start' => 3, 'u_end' => 4, 'cpu' => 30, 'temp' => 45, 'metric_trend' => 0],
            ],
        ],
        'created_by' => User::factory()->docent()->create()->id,
    ]);
}

it('builds a starting world from a blueprint into a student\'s world', function () {
    Queue::fake();
    $student = User::factory()->technicus()->create();

    provisioningScenario()->applyTo($student->id);

    actingAs($student);
    expect(Rack::pluck('name')->all())->toBe(['R03'])
        ->and(Device::pluck('name')->sort()->values()->all())->toBe(['srv-db', 'srv-web'])
        ->and(Device::first()->student_id)->toBe($student->id);
});

it('schedules name-based timed actions against the provisioned devices', function () {
    Queue::fake();
    $student = User::factory()->technicus()->create();

    provisioningScenario()->applyTo($student->id);

    $dbDevice = Device::withoutGlobalScopes()->where('student_id', $student->id)->where('name', 'srv-db')->first();

    Queue::assertPushed(ApplyScenarioAction::class, fn (ApplyScenarioAction $job): bool => $job->deviceId === $dbDevice->id
        && $job->status === DeviceStatus::Storing);
});

it('assigns a scenario from the docent screen, wiping and rebuilding the student world', function () {
    $docent = User::factory()->docent()->create();
    $student = User::factory()->technicus()->create();

    // Pre-existing junk in the student's world that must be wiped.
    actingAs($student);
    Device::factory()->create(['name' => 'old-device']);

    $scenario = provisioningScenario();

    actingAs($docent);
    Livewire::test('pages::students.index')
        ->set("assign.{$student->id}", (string) $scenario->id)
        ->call('assignScenario', $student->id)
        ->assertHasNoErrors();

    expect($student->fresh()->assigned_scenario_id)->toBe($scenario->id);

    actingAs($student);
    expect(Device::pluck('name')->all())->not->toContain('old-device')
        ->and(Device::count())->toBe(2);
});

it('resets a student world by re-applying the assigned scenario', function () {
    Queue::fake();
    $docent = User::factory()->docent()->create();
    $student = User::factory()->technicus()->create();
    $scenario = provisioningScenario();

    $student->forceFill(['assigned_scenario_id' => $scenario->id])->save();

    actingAs($docent);
    Livewire::test('pages::students.index')
        ->call('resetWorld', $student->id)
        ->assertHasNoErrors();

    actingAs($student);
    expect(Device::count())->toBe(2);
});

it('lets a docent add a student account', function () {
    actingAs(User::factory()->docent()->create());

    Livewire::test('pages::students.index')
        ->set('newName', 'Nieuwe Student')
        ->set('newNumber', 'S100001')
        ->set('newEmail', 'nieuw@datacenter-sim.test')
        ->call('createStudent')
        ->assertHasNoErrors();

    $student = User::where('email', 'nieuw@datacenter-sim.test')->first();

    expect($student)->not->toBeNull()
        ->and($student->role)->toBe(Role::Technicus);
});

it('forbids a non-docent from the students screen', function () {
    actingAs(User::factory()->technicus()->create())
        ->get(route('students.index'))
        ->assertForbidden();
});
