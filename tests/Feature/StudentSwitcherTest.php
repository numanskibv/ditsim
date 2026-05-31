<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('shows a coupled technicus only their own and partner world', function () {
    $x = User::factory()->technicus()->create(['name' => 'Xavier']);
    $y = User::factory()->technicus()->create(['name' => 'Yara']);
    $stranger = User::factory()->technicus()->create(['name' => 'Zander']);
    $x->forceFill(['partner_id' => $y->id])->save();
    $y->forceFill(['partner_id' => $x->id])->save();

    actingAs($x->fresh());
    Livewire::test('student-switcher')
        ->assertSee('Mijn omgeving')
        ->assertSee('Partner: Yara')
        ->assertDontSee('Zander');
});

it('hides the switcher for a technicus without a partner', function () {
    actingAs(User::factory()->technicus()->create());

    Livewire::test('student-switcher')
        ->assertDontSee('Actieve student');
});

it('lets a technicus switch to the partner world', function () {
    $x = User::factory()->technicus()->create();
    $y = User::factory()->technicus()->create();
    $x->forceFill(['partner_id' => $y->id])->save();
    $y->forceFill(['partner_id' => $x->id])->save();

    actingAs($x->fresh());
    Livewire::test('student-switcher')
        ->set('activeStudentId', (string) $y->id);

    expect(session('active_student_id'))->toBe($y->id);
});

it('lets a docent couple two students mutually from the management screen', function () {
    $sanne = User::factory()->technicus()->create(['name' => 'Sanne']);
    $sven = User::factory()->technicus()->create(['name' => 'Sven']);

    actingAs(User::factory()->docent()->create());
    Livewire::test('pages::students.index')
        ->set("partner.{$sanne->id}", (string) $sven->id)
        ->call('setPartner', $sanne->id)
        ->assertHasNoErrors();

    expect($sanne->fresh()->partner_id)->toBe($sven->id)
        ->and($sven->fresh()->partner_id)->toBe($sanne->id);
});

it('uncouples both students when the partner is cleared', function () {
    $sanne = User::factory()->technicus()->create();
    $sven = User::factory()->technicus()->create();
    $sanne->forceFill(['partner_id' => $sven->id])->save();
    $sven->forceFill(['partner_id' => $sanne->id])->save();

    actingAs(User::factory()->docent()->create());
    Livewire::test('pages::students.index')
        ->set("partner.{$sanne->id}", '')
        ->call('setPartner', $sanne->id)
        ->assertHasNoErrors();

    expect($sanne->fresh()->partner_id)->toBeNull()
        ->and($sven->fresh()->partner_id)->toBeNull();
});
