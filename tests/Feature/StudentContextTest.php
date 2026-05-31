<?php

use App\Models\User;
use App\Support\CurrentStudent;

use function Pest\Laravel\actingAs;

it('resolves a technicus to their own world', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);

    expect(app(CurrentStudent::class)->id())->toBe($technicus->id);
});

it('resolves to null for a guest', function () {
    expect(app(CurrentStudent::class)->id())->toBeNull();
});

it('lets a shared role select a student world via the session', function () {
    $docent = User::factory()->docent()->create();
    $student = User::factory()->technicus()->create();

    actingAs($docent);
    $resolver = app(CurrentStudent::class);

    expect($resolver->id())->toBeNull();

    $resolver->setActive($student->id);

    expect($resolver->id())->toBe($student->id);

    $resolver->setActive(null);

    expect($resolver->id())->toBeNull();
});

it('forces a world for the duration of runFor and restores it after', function () {
    $technicus = User::factory()->technicus()->create();

    actingAs($technicus);
    $resolver = app(CurrentStudent::class);

    $seen = $resolver->runFor(999, fn (): ?int => $resolver->id());

    expect($seen)->toBe(999)
        ->and($resolver->id())->toBe($technicus->id);
});

it('shows the student switcher to a docent but not to a technicus', function () {
    $student = User::factory()->technicus()->create(['name' => 'Sanne Student']);

    actingAs(User::factory()->docent()->create());
    Livewire::test('student-switcher')
        ->assertSee('Actieve student')
        ->set('activeStudentId', (string) $student->id);

    expect(session('active_student_id'))->toBe($student->id);
});
