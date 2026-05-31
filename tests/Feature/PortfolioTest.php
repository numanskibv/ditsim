<?php

use App\Enums\PortfolioAssignment;
use App\Models\Device;
use App\Models\Rack;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VisitorLog;
use App\Support\PortfolioEvidence;

use function Pest\Laravel\actingAs;

/**
 * Create a ticket with an approved installation plan and some DCIM data.
 */
function approvedInstallationTicket(): Ticket
{
    $rack = Rack::factory()->create();
    Device::factory()->for($rack)->create();

    $ticket = Ticket::factory()->create();
    $plan = $ticket->installationPlan()->create([
        'werkzaamheden' => 'Vervang koelunit',
        'materialen' => 'Koelunit X',
        'middelen' => 'Gereedschap',
        'betrokken_collega' => 'Jan',
        'security_fysiek' => 'Toegangspas',
        'security_virtueel' => 'VPN-only',
    ]);
    $plan->approveBy(User::factory()->leidinggevende()->create());

    return $ticket->fresh();
}

it('downloads a complete portfolio PDF for opdracht 1', function () {
    $ticket = approvedInstallationTicket();

    $response = actingAs(User::factory()->technicus()->create())
        ->get(route('portfolio.pdf', ['assignment' => 1, 'ticket' => $ticket->id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('puts the student name, date, assignment number and werkproces on the PDF', function () {
    $student = User::factory()->technicus()->create(['name' => 'Sam Student']);
    $ticket = approvedInstallationTicket();

    $evidence = app(PortfolioEvidence::class)->gather(PortfolioAssignment::One, $ticket);

    $html = view('pdf.portfolio', [
        'assignment' => PortfolioAssignment::One,
        'student' => $student,
        'date' => now(),
        'data' => $evidence['data'],
    ])->render();

    expect($html)
        ->toContain('Sam Student')
        ->toContain(now()->format('d-m-Y'))
        ->toContain('Opdracht 1')
        ->toContain('Werkproces');
});

it('refuses the PDF and reports what is missing when evidence is incomplete', function () {
    // An open visit (no check-out) makes opdracht 6 incomplete.
    VisitorLog::factory()->create();

    actingAs(User::factory()->technicus()->create())
        ->get(route('portfolio.pdf', ['assignment' => 6]))
        ->assertStatus(422)
        ->assertSee('aan- én afmelding', false);
});

it('completes opdracht 6 once a visitor is fully checked out', function () {
    Rack::factory()->create();
    VisitorLog::factory()->checkedOut()->create();

    $response = actingAs(User::factory()->technicus()->create())
        ->get(route('portfolio.pdf', ['assignment' => 6]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('flags missing parts via the evidence builder', function () {
    expect(app(PortfolioEvidence::class)->gather(PortfolioAssignment::Six)['missing'])->not->toBeEmpty();
});

it('shows the six assignments with a warning when evidence is missing', function () {
    actingAs(User::factory()->technicus()->create());

    Livewire::test('pages::portfolio.index')
        ->assertSee('Opdracht 1')
        ->assertSee('Opdracht 6')
        ->assertSee('Bewijs onvolledig');
});
