<?php

use App\Enums\Ability;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('grants each role exactly its own ability', function (Role $role, Ability $allowed) {
    $user = User::factory()->create(['role' => $role]);

    // Abilities are evaluated for the acting user; a technicus is world-aware
    // and defaults to their own world, where they hold execute-tasks.
    actingAs($user);

    expect($user->can($allowed->value))->toBeTrue();

    foreach (Ability::cases() as $ability) {
        if ($ability !== $allowed) {
            expect($user->can($ability->value))->toBeFalse();
        }
    }
})->with([
    'docent manages scenarios' => [Role::Docent, Ability::ManageScenarios],
    'leidinggevende approves' => [Role::Leidinggevende, Ability::ApproveTasks],
    'technicus executes' => [Role::Technicus, Ability::ExecuteTasks],
    'klant creates reports' => [Role::Klant, Ability::CreateReports],
]);

it('blocks a technicus from approving (four-eyes)', function () {
    $technicus = User::factory()->technicus()->create();

    expect($technicus->can(Ability::ApproveTasks->value))->toBeFalse();
});

it('only allows a leidinggevende to approve', function () {
    expect(User::factory()->leidinggevende()->create()->can(Ability::ApproveTasks->value))->toBeTrue();
    expect(User::factory()->technicus()->create()->can(Ability::ApproveTasks->value))->toBeFalse();
    expect(User::factory()->klant()->create()->can(Ability::ApproveTasks->value))->toBeFalse();
    expect(User::factory()->docent()->create()->can(Ability::ApproveTasks->value))->toBeFalse();
});

it('only allows a docent to manage scenarios', function () {
    expect(User::factory()->docent()->create()->can(Ability::ManageScenarios->value))->toBeTrue();
    expect(User::factory()->technicus()->create()->can(Ability::ManageScenarios->value))->toBeFalse();
});

it('casts the role attribute to the Role enum', function () {
    expect(User::factory()->technicus()->create()->role)->toBe(Role::Technicus);
});

it('shows each role its own navigation item and hides the others', function (Role $role, string $visible, string $hidden) {
    $user = User::factory()->create(['role' => $role]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($visible)
        ->assertDontSee($hidden);
})->with([
    'docent' => [Role::Docent, 'Scenario\'s beheren', 'Goedkeuringen'],
    'leidinggevende' => [Role::Leidinggevende, 'Goedkeuringen', 'Scenario\'s beheren'],
    'technicus' => [Role::Technicus, 'Opdrachten uitvoeren', 'Goedkeuringen'],
    'klant' => [Role::Klant, 'Melding maken', 'Opdrachten uitvoeren'],
]);
