<?php

use App\Enums\InstallationPlanStatus;
use App\Models\InstallationPlan;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CurrentStudent;

use function Pest\Laravel\actingAs;

/**
 * Create a complete, ready-for-approval plan for the given ticket.
 */
function readyPlanFor(Ticket $ticket): InstallationPlan
{
    $plan = $ticket->installationPlan()->create([
        'werkzaamheden' => 'Vervang de koelunit in R03.',
        'materialen' => 'Koelunit type X, montagerail.',
        'middelen' => 'Steeksleutelset, ladder.',
        'betrokken_collega' => 'Jan de Vries',
        'security_fysiek' => 'Toegangspas + begeleiding.',
        'security_virtueel' => 'Wijziging via change-window, VPN-only.',
    ]);

    $plan->markReady();

    return $plan;
}

it('reports the missing mandatory sections', function () {
    $ticket = Ticket::factory()->create();
    $plan = $ticket->installationPlan()->create(['werkzaamheden' => 'alleen dit']);

    expect($plan->isComplete())->toBeFalse()
        ->and($plan->missingSections())->toContain('Materialenlijst')
        ->and($plan->missingSections())->toContain('Securitymaatregelen (virtueel)');
});

it('cannot mark a plan ready while a mandatory section is empty', function () {
    $ticket = Ticket::factory()->create();

    actingAs(User::factory()->technicus()->create());
    Livewire::test('pages::plans.edit', ['ticket' => $ticket])
        ->set('werkzaamheden', 'x')
        ->set('materialen', 'x')
        ->set('middelen', 'x')
        ->set('betrokken_collega', 'x')
        ->set('security_fysiek', 'x')
        ->set('security_virtueel', '')
        ->call('markReady')
        ->assertHasErrors('security_virtueel');

    expect(optional($ticket->fresh()->installationPlan)->ready_at)->toBeNull();
});

it('marks a plan ready once every section is filled', function () {
    $ticket = Ticket::factory()->create();

    actingAs(User::factory()->technicus()->create());
    Livewire::test('pages::plans.edit', ['ticket' => $ticket])
        ->set('werkzaamheden', 'Vervang koelunit')
        ->set('materialen', 'Koelunit X')
        ->set('middelen', 'Gereedschap')
        ->set('betrokken_collega', 'Jan')
        ->set('security_fysiek', 'Toegangspas')
        ->set('security_virtueel', 'VPN-only')
        ->call('markReady')
        ->assertHasNoErrors();

    expect($ticket->fresh()->installationPlan->ready_at)->not->toBeNull();
});

it('lets a leidinggevende approve a ready plan but forbids a technicus', function () {
    $ticket = Ticket::factory()->create();
    $plan = readyPlanFor($ticket);

    actingAs(User::factory()->technicus()->create());
    Livewire::test('pages::plans.edit', ['ticket' => $ticket])
        ->call('approve')
        ->assertForbidden();

    expect($plan->fresh()->isApproved())->toBeFalse();

    $manager = User::factory()->leidinggevende()->create();
    actingAs($manager);
    app(CurrentStudent::class)->setActive(User::factory()->technicus()->create()->id);
    Livewire::test('pages::plans.edit', ['ticket' => $ticket])
        ->call('approve')
        ->assertHasNoErrors();

    $plan->refresh();
    expect($plan->isApproved())->toBeTrue()
        ->and($plan->approved_by)->toBe($manager->id)
        ->and($plan->approved_at)->not->toBeNull();
});

it('lets a leidinggevende reject a plan with a reason', function () {
    $ticket = Ticket::factory()->create();
    readyPlanFor($ticket);

    actingAs(User::factory()->leidinggevende()->create());
    app(CurrentStudent::class)->setActive(User::factory()->technicus()->create()->id);
    Livewire::test('pages::plans.edit', ['ticket' => $ticket])
        ->set('rejectReason', 'Materialenlijst is onvolledig')
        ->call('reject')
        ->assertHasNoErrors();

    $plan = $ticket->fresh()->installationPlan;
    expect($plan->status)->toBe(InstallationPlanStatus::Afgekeurd)
        ->and($plan->rejection_reason)->toBe('Materialenlijst is onvolledig');
});

it('blocks the PDF download until the plan is approved', function () {
    $ticket = Ticket::factory()->create();
    readyPlanFor($ticket);

    actingAs(User::factory()->technicus()->create())
        ->get(route('plans.pdf', $ticket))
        ->assertForbidden();
});

it('downloads the approved plan as a PDF', function () {
    $ticket = Ticket::factory()->create();
    $plan = readyPlanFor($ticket);
    $plan->approveBy(User::factory()->leidinggevende()->create());

    $response = actingAs(User::factory()->technicus()->create())
        ->get(route('plans.pdf', $ticket));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('renders the PDF view with all five sections and the sign-off', function () {
    $ticket = Ticket::factory()->create(['title' => 'Koelunit vervangen']);
    $plan = readyPlanFor($ticket);
    $manager = User::factory()->leidinggevende()->create(['name' => 'Laura Manager']);
    $plan->approveBy($manager);

    $html = view('pdf.installation-plan', [
        'ticket' => $ticket->fresh(),
        'plan' => $plan->fresh()->load('approver'),
    ])->render();

    expect($html)
        ->toContain('Werkzaamheden')
        ->toContain('Materialenlijst')
        ->toContain('Middelenlijst')
        ->toContain('Betrokken collega')
        ->toContain('Securitymaatregelen')
        ->toContain('Goedgekeurd door Laura Manager');
});
