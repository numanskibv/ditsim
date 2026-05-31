<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Configurable so the cloud can ship a strong password instead of the
        // local default; see config/datacenter.php.
        $password = Hash::make(config('datacenter.default_password'));

        User::factory()->technicus()->create([
            'name' => 'Tessa Technicus',
            'student_number' => 'S100001',
            'email' => 'technicus@datacenter-sim.test',
            'password' => $password,
        ]);

        // Extra student-technici so multiple students can work in parallel,
        // each in their own isolated world. Sanne and Sven form a couple: each
        // acts as the leidinggevende/klant counter-role in the other's world.
        $sanne = User::factory()->technicus()->create([
            'name' => 'Sanne Student',
            'student_number' => 'S100002',
            'email' => 'sanne@datacenter-sim.test',
            'password' => $password,
        ]);

        $sven = User::factory()->technicus()->create([
            'name' => 'Sven Student',
            'student_number' => 'S100003',
            'email' => 'sven@datacenter-sim.test',
            'password' => $password,
        ]);

        $sanne->forceFill(['partner_id' => $sven->id])->save();
        $sven->forceFill(['partner_id' => $sanne->id])->save();

        User::factory()->leidinggevende()->create([
            'name' => 'Laura Leidinggevende',
            'email' => 'leidinggevende@datacenter-sim.test',
            'password' => $password,
        ]);

        User::factory()->docent()->create([
            'name' => 'Dirk Docent',
            'email' => 'docent@datacenter-sim.test',
            'password' => $password,
        ]);

        // Demo context: the customer organisation MediCloud BV.
        User::factory()->klant()->create([
            'name' => 'MediCloud BV',
            'email' => 'klant@medicloud.test',
            'password' => $password,
        ]);

        $this->call(DcimSeeder::class);
        $this->call(TicketSeeder::class);
        $this->call(DemoSeeder::class);
        $this->call(ScenarioLibrarySeeder::class);
    }
}
