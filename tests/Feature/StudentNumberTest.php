<?php

use App\Models\Rack;
use App\Models\User;
use App\Models\VisitorLog;

use function Pest\Laravel\actingAs;

it('lets a docent add a student with a student number', function () {
    actingAs(User::factory()->docent()->create());

    Livewire::test('pages::students.index')
        ->set('newName', 'Jan Jansen')
        ->set('newNumber', 'S123456')
        ->set('newEmail', 'jan@school.test')
        ->call('createStudent')
        ->assertHasNoErrors();

    expect(User::where('email', 'jan@school.test')->first()->student_number)->toBe('S123456');
});

it('requires a student number when adding a student', function () {
    actingAs(User::factory()->docent()->create());

    Livewire::test('pages::students.index')
        ->set('newName', 'Zonder Nummer')
        ->set('newEmail', 'zonder@school.test')
        ->call('createStudent')
        ->assertHasErrors('newNumber');
});

it('lets a docent set a student number from the list', function () {
    $student = User::factory()->technicus()->create();

    actingAs(User::factory()->docent()->create());
    Livewire::test('pages::students.index')
        ->set("studentNumber.{$student->id}", 'S999000')
        ->call('saveStudentNumber', $student->id)
        ->assertHasNoErrors();

    expect($student->fresh()->student_number)->toBe('S999000');
});

it('puts the student name and number in the portfolio PDF filename', function () {
    $student = User::factory()->technicus()->create(['name' => 'Jan Jansen', 'student_number' => 'S123456']);

    // Opdracht 6 is compleet met een volledig afgehandeld bezoek.
    actingAs($student);
    Rack::factory()->create();
    VisitorLog::factory()->checkedOut()->create();

    $response = actingAs($student)->get(route('portfolio.pdf', ['assignment' => 6]));

    $response->assertOk();
    $disposition = $response->headers->get('content-disposition');

    expect($disposition)->toContain('portfoliobewijs-opdracht-6-jan-jansen-s123456.pdf');
});
