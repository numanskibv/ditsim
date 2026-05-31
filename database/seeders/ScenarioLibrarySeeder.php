<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Scenario;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * A library of ready-to-assign training scenarios. Each one is a *provisioning*
 * scenario: it builds a complete starting world (rack + devices) in a student's
 * environment, optionally with timed events that unfold after the start. The
 * docent assigns one per student via "Studenten beheren".
 */
class ScenarioLibrarySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $docent = User::where('role', Role::Docent->value)->first();

        foreach ($this->scenarios() as $scenario) {
            Scenario::updateOrCreate(
                ['name' => $scenario['name']],
                [
                    'description' => $scenario['description'],
                    'actions' => $scenario['actions'] ?? [],
                    'blueprint' => $scenario['blueprint'],
                    'created_by' => $docent?->id,
                ],
            );
        }
    }

    /**
     * Shorthand for one device in a blueprint.
     *
     * @return array<string, mixed>
     */
    private function device(string $name, string $type, string $status, int $uStart, int $uEnd, int $cpu, int $temp, int $trend = 0): array
    {
        return [
            'name' => $name, 'type' => $type, 'status' => $status,
            'u_start' => $uStart, 'u_end' => $uEnd,
            'cpu' => $cpu, 'temp' => $temp, 'metric_trend' => $trend,
        ];
    }

    /**
     * The 15 scenarios.
     *
     * @return list<array{name: string, description: string, blueprint: array<string, mixed>, actions?: list<array<string, mixed>>}>
     */
    private function scenarios(): array
    {
        return [
            [
                'name' => '01 · Gezonde basisopstelling',
                'description' => 'Een rustig, gezond rack. Ideaal om de omgeving te leren kennen en een rondje monitoring te doen.',
                'blueprint' => ['rack' => ['name' => 'R01', 'location' => 'DC-Amsterdam', 'height_u' => 42], 'devices' => [
                    $this->device('app-web01', 'server', 'actief', 1, 2, 25, 42),
                    $this->device('app-web02', 'server', 'actief', 3, 4, 30, 44),
                    $this->device('core-sw01', 'switch', 'actief', 10, 10, 12, 35),
                    $this->device('edge-fw01', 'firewall', 'actief', 12, 12, 18, 38),
                ]],
            ],
            [
                'name' => '02 · Webserver loopt warm (predictief)',
                'description' => 'De temperatuur van een webserver loopt elke tick op. Grijp in tijdens de waarschuwingsfase, vóór de storing (opdracht 4).',
                'blueprint' => ['rack' => ['name' => 'R02', 'location' => 'DC-Amsterdam', 'height_u' => 42], 'devices' => [
                    $this->device('web-front01', 'server', 'actief', 1, 2, 40, 55, 8),
                    $this->device('web-front02', 'server', 'actief', 3, 4, 35, 50),
                    $this->device('lb-sw01', 'switch', 'actief', 10, 10, 15, 34),
                ]],
            ],
            [
                'name' => '03 · Database in storing',
                'description' => 'De primaire database is uitgevallen. Neem de melding aan, stel diagnose en handel het incident af (opdracht 5).',
                'blueprint' => ['rack' => ['name' => 'R03', 'location' => 'DC-Utrecht', 'height_u' => 42], 'devices' => [
                    $this->device('db-primary', 'server', 'storing', 5, 8, 80, 86),
                    $this->device('db-replica', 'server', 'actief', 9, 12, 45, 52),
                    $this->device('db-sw01', 'switch', 'actief', 14, 14, 20, 36),
                ]],
            ],
            [
                'name' => '04 · Netwerkswitch offline',
                'description' => 'Een kernswitch is offline; een deel van het netwerk ligt eruit. Lokaliseer en herstel (opdracht 2/5).',
                'blueprint' => ['rack' => ['name' => 'R04', 'location' => 'DC-Utrecht', 'height_u' => 42], 'devices' => [
                    $this->device('core-sw01', 'switch', 'offline', 10, 10, 0, 0),
                    $this->device('core-sw02', 'switch', 'actief', 11, 11, 22, 38),
                    $this->device('app-srv01', 'server', 'actief', 1, 2, 30, 45),
                    $this->device('app-srv02', 'server', 'actief', 3, 4, 28, 44),
                ]],
            ],
            [
                'name' => '05 · Koeling valt geleidelijk uit',
                'description' => 'Eerst een vroeg signaal, daarna een echte storing. Goede oefening voor NOC-bewaking en escaleren (opdracht 2/3).',
                'blueprint' => ['rack' => ['name' => 'R05', 'location' => 'DC-Rotterdam', 'height_u' => 42], 'devices' => [
                    $this->device('comp-srv01', 'server', 'actief', 1, 2, 50, 60, 6),
                    $this->device('comp-srv02', 'server', 'actief', 3, 4, 55, 62, 6),
                    $this->device('comp-sw01', 'switch', 'actief', 10, 10, 18, 40),
                ]],
                'actions' => [
                    ['delay' => 60, 'device' => 'comp-srv01', 'status' => 'waarschuwing'],
                    ['delay' => 180, 'device' => 'comp-srv01', 'status' => 'storing'],
                ],
            ],
            [
                'name' => '06 · Firewall in storing (security)',
                'description' => 'De firewall is uitgevallen — een securitygevoelig incident. Handel zorgvuldig en schakel waar nodig op (opdracht 5).',
                'blueprint' => ['rack' => ['name' => 'R06', 'location' => 'DC-Rotterdam', 'height_u' => 42], 'devices' => [
                    $this->device('perimeter-fw01', 'firewall', 'storing', 12, 12, 5, 70),
                    $this->device('perimeter-fw02', 'firewall', 'actief', 13, 13, 20, 40),
                    $this->device('dmz-sw01', 'switch', 'actief', 10, 10, 16, 35),
                ]],
            ],
            [
                'name' => '07 · Opslag bijna vol',
                'description' => 'Een storage-array nadert kritieke belasting (waarschuwing). Onderneem preventief actie (opdracht 4).',
                'blueprint' => ['rack' => ['name' => 'R07', 'location' => 'DC-Eindhoven', 'height_u' => 42], 'devices' => [
                    $this->device('san-node01', 'storage', 'waarschuwing', 1, 4, 88, 58),
                    $this->device('san-node02', 'storage', 'actief', 5, 8, 60, 50),
                    $this->device('san-sw01', 'switch', 'actief', 14, 14, 19, 36),
                ]],
            ],
            [
                'name' => '08 · Router flapt en valt uit',
                'description' => 'Een router geeft een waarschuwing en gaat na enige tijd in storing. Bewaak en grijp in (opdracht 2/4).',
                'blueprint' => ['rack' => ['name' => 'R08', 'location' => 'DC-Eindhoven', 'height_u' => 42], 'devices' => [
                    $this->device('wan-rtr01', 'router', 'waarschuwing', 20, 20, 86, 62),
                    $this->device('wan-rtr02', 'router', 'actief', 21, 21, 30, 44),
                    $this->device('core-sw01', 'switch', 'actief', 10, 10, 18, 38),
                ]],
                'actions' => [
                    ['delay' => 120, 'device' => 'wan-rtr01', 'status' => 'storing'],
                ],
            ],
            [
                'name' => '09 · Volle productierij (gemengd)',
                'description' => 'Een goed gevuld rack met een mix van apparatuur en statussen. Verschaf overzicht en prioriteer (opdracht 2).',
                'blueprint' => ['rack' => ['name' => 'R09', 'location' => 'DC-Utrecht', 'height_u' => 42], 'devices' => [
                    $this->device('prod-srv01', 'server', 'actief', 1, 2, 35, 48),
                    $this->device('prod-srv02', 'server', 'waarschuwing', 3, 4, 70, 66),
                    $this->device('prod-db01', 'server', 'actief', 5, 8, 55, 58),
                    $this->device('prod-stor01', 'storage', 'actief', 9, 12, 50, 52),
                    $this->device('prod-sw01', 'switch', 'actief', 14, 14, 22, 38),
                    $this->device('prod-fw01', 'firewall', 'actief', 16, 16, 18, 37),
                ]],
            ],
            [
                'name' => '10 · Stille nacht (monitoring)',
                'description' => 'Alles draait, maar één server bouwt langzaam temperatuur op. Oefen geduldig bewaken en het vroege signaal herkennen.',
                'blueprint' => ['rack' => ['name' => 'R10', 'location' => 'DC-Amsterdam', 'height_u' => 42], 'devices' => [
                    $this->device('night-srv01', 'server', 'actief', 1, 2, 20, 40, 4),
                    $this->device('night-srv02', 'server', 'actief', 3, 4, 22, 41),
                    $this->device('night-sw01', 'switch', 'actief', 10, 10, 10, 33),
                ]],
            ],
            [
                'name' => '11 · Cascade-storing',
                'description' => 'Meerdere apparaten vallen kort na elkaar uit. Houd het hoofd koel, prioriteer met de SLA en escaleer (opdracht 2/5).',
                'blueprint' => ['rack' => ['name' => 'R11', 'location' => 'DC-Rotterdam', 'height_u' => 42], 'devices' => [
                    $this->device('cas-srv01', 'server', 'actief', 1, 2, 45, 55),
                    $this->device('cas-srv02', 'server', 'actief', 3, 4, 48, 57),
                    $this->device('cas-sw01', 'switch', 'actief', 10, 10, 20, 38),
                    $this->device('cas-db01', 'server', 'actief', 5, 8, 50, 58),
                ]],
                'actions' => [
                    ['delay' => 45, 'device' => 'cas-sw01', 'status' => 'waarschuwing'],
                    ['delay' => 90, 'device' => 'cas-srv01', 'status' => 'storing'],
                    ['delay' => 150, 'device' => 'cas-db01', 'status' => 'storing'],
                ],
            ],
            [
                'name' => '12 · Nieuwe klant: rack inrichten',
                'description' => 'Een vrijwel leeg rack voor een nieuwe klant. Maak een installatieplan, laat het goedkeuren en plaats de apparatuur (opdracht 1).',
                'blueprint' => ['rack' => ['name' => 'R12', 'location' => 'DC-Eindhoven', 'height_u' => 42], 'devices' => [
                    $this->device('klant-sw01', 'switch', 'actief', 1, 1, 10, 32),
                ]],
            ],
            [
                'name' => '13 · Onderhoudsronde',
                'description' => 'Verschillende apparaten vragen aandacht. Loop een inspectieronde, signaleer en communiceer naar een collega (opdracht 3).',
                'blueprint' => ['rack' => ['name' => 'R13', 'location' => 'DC-Utrecht', 'height_u' => 42], 'devices' => [
                    $this->device('maint-srv01', 'server', 'waarschuwing', 1, 2, 78, 67),
                    $this->device('maint-srv02', 'server', 'actief', 3, 4, 40, 50),
                    $this->device('maint-stor01', 'storage', 'waarschuwing', 5, 8, 85, 60),
                    $this->device('maint-sw01', 'switch', 'actief', 14, 14, 20, 39),
                ]],
            ],
            [
                'name' => '14 · Hittegolf in de serverruimte',
                'description' => 'Bijna alles loopt warm en blijft oplopen. Bewaak scherp en grijp tijdig in voordat het escaleert (opdracht 2/4).',
                'blueprint' => ['rack' => ['name' => 'R14', 'location' => 'DC-Rotterdam', 'height_u' => 42], 'devices' => [
                    $this->device('heat-srv01', 'server', 'actief', 1, 2, 60, 63, 7),
                    $this->device('heat-srv02', 'server', 'actief', 3, 4, 58, 64, 7),
                    $this->device('heat-srv03', 'server', 'waarschuwing', 5, 6, 75, 68, 5),
                    $this->device('heat-sw01', 'switch', 'actief', 10, 10, 25, 45),
                ]],
            ],
            [
                'name' => '15 · Edge-locatie met beperkte redundantie',
                'description' => 'Een kleine edge-opstelling: weinig reserve, dus elk incident telt. Oefen beslissen en op tijd escaleren (opdracht 5).',
                'blueprint' => ['rack' => ['name' => 'R15', 'location' => 'DC-Groningen', 'height_u' => 24], 'devices' => [
                    $this->device('edge-srv01', 'server', 'actief', 1, 2, 50, 56, 5),
                    $this->device('edge-rtr01', 'router', 'actief', 6, 6, 30, 44),
                    $this->device('edge-fw01', 'firewall', 'waarschuwing', 8, 8, 72, 64),
                ]],
                'actions' => [
                    ['delay' => 90, 'device' => 'edge-srv01', 'status' => 'waarschuwing'],
                ],
            ],
        ];
    }
}
