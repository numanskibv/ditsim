<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Create the fixed MediCloud demo accounts (Tessa-technicus + the two coupled
 * students, the leidinggevende, docent and the MediCloud klant). Idempotent:
 * each account is keyed on its e-mail, so running this on a database that
 * already has them is a no-op. Shared by DatabaseSeeder (fresh install) and the
 * simulate:import-demo command (so the demo always has a real owner).
 */
class DemoAccountsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Configurable so the cloud can ship a strong password instead of the
        // local default; see config/datacenter.php.
        $password = Hash::make(config('datacenter.default_password'));

        $this->ensureUser('technicus@datacenter-sim.test', fn () => User::factory()->technicus()->create([
            'name' => 'Tessa Technicus',
            'student_number' => 'S100001',
            'email' => 'technicus@datacenter-sim.test',
            'password' => $password,
        ]));

        // Extra student-technici so multiple students can work in parallel,
        // each in their own isolated world. Sanne and Sven form a couple: each
        // acts as the leidinggevende/klant counter-role in the other's world.
        $sanne = $this->ensureUser('sanne@datacenter-sim.test', fn () => User::factory()->technicus()->create([
            'name' => 'Sanne Student',
            'student_number' => 'S100002',
            'email' => 'sanne@datacenter-sim.test',
            'password' => $password,
        ]));

        $sven = $this->ensureUser('sven@datacenter-sim.test', fn () => User::factory()->technicus()->create([
            'name' => 'Sven Student',
            'student_number' => 'S100003',
            'email' => 'sven@datacenter-sim.test',
            'password' => $password,
        ]));

        $sanne->forceFill(['partner_id' => $sven->id])->save();
        $sven->forceFill(['partner_id' => $sanne->id])->save();

        $this->ensureUser('leidinggevende@datacenter-sim.test', fn () => User::factory()->leidinggevende()->create([
            'name' => 'Laura Leidinggevende',
            'email' => 'leidinggevende@datacenter-sim.test',
            'password' => $password,
        ]));

        $this->ensureUser('docent@datacenter-sim.test', fn () => User::factory()->docent()->create([
            'name' => 'Dirk Docent',
            'email' => 'docent@datacenter-sim.test',
            'password' => $password,
        ]));

        // Demo context: the customer organisation MediCloud BV.
        $this->ensureUser('klant@medicloud.test', fn () => User::factory()->klant()->create([
            'name' => 'MediCloud BV',
            'email' => 'klant@medicloud.test',
            'password' => $password,
        ]));
    }

    /**
     * Return the existing user with this e-mail, or create it via the factory.
     *
     * @param  callable(): User  $make
     */
    private function ensureUser(string $email, callable $make): User
    {
        return User::where('email', $email)->first() ?? $make();
    }
}
