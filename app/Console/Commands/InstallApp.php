<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * First-boot installer for cloud deployments: always migrate, and seed the demo
 * data only when the database is still empty. Safe to run on every deploy and
 * database-agnostic (no SQLite file flag), so it works on managed Postgres/MySQL
 * where the filesystem is not persistent.
 */
#[Signature('app:install')]
#[Description('Migrate the database and seed the demo data once (idempotent).')]
class InstallApp extends Command
{
    public function handle(): int
    {
        $this->info('→ Migraties uitvoeren…');
        $this->call('migrate', ['--force' => true]);

        if (User::query()->count() > 0) {
            $this->info('→ Database bevat al data: seeden overgeslagen.');

            return self::SUCCESS;
        }

        if (! config('datacenter.seed_demo')) {
            $this->info('→ SEED_DEMO=false: schoon gestart. De eerste registratie wordt de docent.');

            return self::SUCCESS;
        }

        $this->info('→ Lege database: demodata seeden…');
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
