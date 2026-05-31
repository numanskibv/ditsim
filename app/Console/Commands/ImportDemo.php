<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Support\StudentWorld;
use Database\Seeders\DcimSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\TicketSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * (Re)imports the MediCloud demo content for testing: the demo world (rack R03,
 * devices, example tickets, plan, messages, inspection, visitors) is rebuilt in
 * the demo owner's world (the first technicus). Existing demo-world data is
 * cleared first so it is safe to run repeatedly; other students' worlds, the
 * accounts and the scenario library are left untouched.
 */
#[Signature('simulate:import-demo')]
#[Description('(Re)import the MediCloud demo data for testing.')]
class ImportDemo extends Command
{
    public function handle(StudentWorld $worlds): int
    {
        $owner = User::where('role', Role::Technicus->value)->first();

        if ($owner !== null) {
            $worlds->wipe($owner->id);
        }

        $this->call('db:seed', ['--class' => DcimSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => TicketSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->info('Demodata (opnieuw) geïmporteerd.');

        return self::SUCCESS;
    }
}
