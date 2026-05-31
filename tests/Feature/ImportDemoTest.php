<?php

use App\Models\Scenario;
use App\Models\Scopes\StudentScope;
use App\Models\Ticket;
use App\Models\User;

/**
 * Counts tickets across every world (the demo lives in the technicus' world,
 * so the student global scope would otherwise hide them).
 */
function demoTicketCount(): int
{
    return Ticket::withoutGlobalScope(StudentScope::class)->count();
}

it('imports the demo on a clean database and creates the demo accounts', function () {
    // Mimics a clean production start: no demo accounts exist yet.
    expect(User::where('email', 'technicus@datacenter-sim.test')->exists())->toBeFalse();

    $this->artisan('simulate:import-demo')->assertSuccessful();

    $technicus = User::where('email', 'technicus@datacenter-sim.test')->first();
    expect($technicus)->not->toBeNull();
    expect(User::where('email', 'klant@medicloud.test')->exists())->toBeTrue();

    // The demo content belongs to the technicus' world (not ownerless).
    expect(demoTicketCount())->toBeGreaterThan(0);
    expect(Ticket::withoutGlobalScope(StudentScope::class)->where('number', 'INC-2026-0001')->value('student_id'))
        ->toBe($technicus->id);

    // The library of 15 assignable scenarios is seeded too.
    expect(Scenario::count())->toBeGreaterThanOrEqual(15);
});

it('can be re-imported without duplicate-key errors or duplicate rows', function () {
    $this->artisan('simulate:import-demo')->assertSuccessful();
    $ticketsAfterFirst = demoTicketCount();
    $scenariosAfterFirst = Scenario::count();

    // Previously this threw SQLSTATE[23505] on tickets_number_unique.
    $this->artisan('simulate:import-demo')->assertSuccessful();

    expect(demoTicketCount())->toBe($ticketsAfterFirst);
    expect(Scenario::count())->toBe($scenariosAfterFirst);
    expect(User::where('email', 'technicus@datacenter-sim.test')->count())->toBe(1);
    expect(Ticket::withoutGlobalScope(StudentScope::class)->where('number', 'INC-2026-0001')->count())->toBe(1);
});
