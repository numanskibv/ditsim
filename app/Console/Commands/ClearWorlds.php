<?php

namespace App\Console\Commands;

use App\Support\StudentWorld;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Removes all simulation/world data (the demo content and any student work),
 * keeping user accounts, couples and the scenario library. The in-app
 * equivalent of the "Demodata verwijderen" button.
 */
#[Signature('simulate:clear')]
#[Description('Delete all simulation/world data; keep accounts and scenarios.')]
class ClearWorlds extends Command
{
    public function handle(StudentWorld $worlds): int
    {
        $worlds->clearAll();

        $this->info('Alle simulatie-/wereldgegevens verwijderd; accounts en scenario\'s behouden.');

        return self::SUCCESS;
    }
}
