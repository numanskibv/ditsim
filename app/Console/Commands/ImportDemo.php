<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\StudentWorld;
use Database\Seeders\DcimSeeder;
use Database\Seeders\DemoAccountsSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ScenarioLibrarySeeder;
use Database\Seeders\TicketSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * (Re)imports the MediCloud demo content for testing: the demo world (rack R03,
 * devices, example tickets, plan, messages, inspection, visitors) is rebuilt in
 * the demo technicus' world (Tessa). The fixed demo accounts are ensured first
 * (idempotent), so the import works — and is visible — even on a clean install
 * that has no demo accounts yet. Existing demo-world data is cleared first so it
 * is safe to run repeatedly. The library of 15 assignable scenarios is seeded
 * too (idempotent), and other students' worlds are left untouched.
 */
#[Signature('simulate:import-demo')]
#[Description('(Re)import the MediCloud demo data for testing.')]
class ImportDemo extends Command
{
    public function handle(StudentWorld $worlds): int
    {
        // Garandeer de MediCloud-demo-accounts (idempotent) zodat de demo een
        // echte eigenaar heeft — ook op een schoon gestarte productie.
        $this->call('db:seed', ['--class' => DemoAccountsSeeder::class, '--force' => true]);

        $owner = User::where('email', 'technicus@datacenter-sim.test')->first();

        if ($owner !== null) {
            $worlds->wipe($owner->id);
        }

        $this->call('db:seed', ['--class' => DcimSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => TicketSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        // De bibliotheek van 15 toewijsbare scenario's (idempotent op naam).
        $this->call('db:seed', ['--class' => ScenarioLibrarySeeder::class, '--force' => true]);

        $this->info('Demodata (opnieuw) geïmporteerd.');

        return self::SUCCESS;
    }
}
