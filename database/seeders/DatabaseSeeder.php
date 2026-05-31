<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DemoAccountsSeeder::class);
        $this->call(DcimSeeder::class);
        $this->call(TicketSeeder::class);
        $this->call(DemoSeeder::class);
        $this->call(ScenarioLibrarySeeder::class);
    }
}
