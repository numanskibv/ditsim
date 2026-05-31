<?php

use App\Enums\Ability;
use App\Models\User;
use App\Support\CurrentStudent;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

/**
 * Create a coupled pair of student-technici (X <-> Y).
 *
 * @return array{0: User, 1: User}
 */
function couple(): array
{
    $x = User::factory()->technicus()->create();
    $y = User::factory()->technicus()->create();
    $x->forceFill(['partner_id' => $y->id])->save();
    $y->forceFill(['partner_id' => $x->id])->save();

    return [$x->fresh(), $y->fresh()];
}

it('defaults a student to their own world', function () {
    [$x] = couple();

    actingAs($x);

    expect(app(CurrentStudent::class)->id())->toBe($x->id);
});

it('lets a student switch to their partner world but not a stranger world', function () {
    [$x, $y] = couple();
    $stranger = User::factory()->technicus()->create();

    actingAs($x);
    $resolver = app(CurrentStudent::class);

    $resolver->setActive($y->id);
    expect($resolver->id())->toBe($y->id);

    // A non-partner selection is ignored and falls back to the own world.
    $resolver->setActive($stranger->id);
    expect($resolver->id())->toBe($x->id);
});

it('grants approve and create only inside the partner world', function () {
    [$x, $y] = couple();

    actingAs($x);
    $resolver = app(CurrentStudent::class);

    // In the own world: a student is a technicus, not a manager/customer.
    expect(Gate::allows(Ability::ApproveTasks->value))->toBeFalse()
        ->and(Gate::allows(Ability::CreateReports->value))->toBeFalse()
        ->and(Gate::allows(Ability::ExecuteTasks->value))->toBeTrue();

    // In the partner world: counter-role grants approve + create, never execute.
    $resolver->setActive($y->id);
    expect(Gate::allows(Ability::ApproveTasks->value))->toBeTrue()
        ->and(Gate::allows(Ability::CreateReports->value))->toBeTrue()
        ->and(Gate::allows(Ability::ExecuteTasks->value))->toBeFalse();
});

it('does not grant counter-role rights toward a non-partner world', function () {
    [$x] = couple();
    $stranger = User::factory()->technicus()->create();

    actingAs($x);
    // Selecting a stranger falls back to own world, so no counter-role rights.
    app(CurrentStudent::class)->setActive($stranger->id);

    expect(Gate::allows(Ability::ApproveTasks->value))->toBeFalse();
});
