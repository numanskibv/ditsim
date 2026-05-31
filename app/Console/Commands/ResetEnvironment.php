<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Factory reset: wipe all application data and reseed the demo environment.
 *
 * This is the in-app equivalent of `migrate:fresh --seed` for cloud
 * deployments, where the teacher has no terminal. It deletes data rather than
 * dropping tables, so it is not blocked by DB::prohibitDestructiveCommands in
 * production and works the same on SQLite and managed databases.
 */
#[Signature('simulate:reset')]
#[Description('Wipe all data and reseed the demo environment (factory reset).')]
class ResetEnvironment extends Command
{
    /**
     * Tables to clear, ordered children-before-parents so plain deletes never
     * violate a foreign key (no need to toggle FK enforcement).
     *
     * @var list<string>
     */
    private const TABLES = [
        // Simulation data.
        'device_alerts',
        'installation_plans',
        'messages',
        'inspection_reports',
        'visitor_logs',
        'tickets',
        'devices',
        'racks',
        'scenarios',
        // Identity.
        'passkeys',
        'users',
        // Runtime/infra (clearing sessions logs everyone out, as intended).
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        // Break the circular user <-> scenario references first so users and
        // scenarios can be deleted without disabling foreign keys.
        if (Schema::hasTable('users')) {
            DB::table('users')->update(['assigned_scenario_id' => null, 'partner_id' => null]);
        }

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $this->info('Lesomgeving gereset naar de fabrieksinstelling.');

        return self::SUCCESS;
    }
}
