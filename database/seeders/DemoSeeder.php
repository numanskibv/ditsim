<?php

namespace Database\Seeders;

use App\Enums\InspectionStatus;
use App\Enums\InstallationPlanStatus;
use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Device;
use App\Models\InspectionReport;
use App\Models\Message;
use App\Models\Scenario;
use App\Models\Scopes\StudentScope;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Fill every module with demo data so all screens and the portfolio
     * exports are populated. Model events are disabled during seeding, so
     * audit/number fields are set explicitly.
     */
    /**
     * The student world the demo data belongs to.
     */
    private ?int $world = null;

    public function run(): void
    {
        $klant = User::where('email', 'klant@medicloud.test')->first();
        $technicus = User::where('email', 'technicus@datacenter-sim.test')->first();
        $leiding = User::where('role', Role::Leidinggevende->value)->first();
        $docent = User::where('role', Role::Docent->value)->first();

        $this->world = $technicus?->id;

        $db = Device::where('name', 'medicloud-db01')->first();
        $app01 = Device::where('name', 'medicloud-app01')->first();

        $this->seedApprovedInstallation($db, $klant, $technicus, $leiding);
        $this->seedMessages($technicus, $leiding, $klant);
        $this->seedInspection($technicus, $db);
        $this->seedScenario($docent, $db, $app01);
        $this->seedVisitors($technicus);
    }

    /**
     * Opdracht 1: a change ticket with an approved installation plan.
     */
    protected function seedApprovedInstallation(?Device $device, ?User $klant, ?User $technicus, ?User $leiding): void
    {
        // Idempotent op het unieke ticketnummer, zodat (her)import niet botst.
        $ticket = Ticket::withoutGlobalScope(StudentScope::class)
            ->firstOrNew(['number' => 'CHG-2026-0001']);
        $ticket->forceFill([
            'number' => 'CHG-2026-0001',
            'type' => TicketType::Change->value,
            'title' => 'Installatie extra koelunit in R03',
            'description' => 'Plaatsen en aansluiten van een extra koelunit voor MediCloud.',
            'status' => TicketStatus::Afgesloten->value,
            'priority' => TicketPriority::P2->value,
            'sla_minutes' => TicketPriority::P2->slaMinutes(),
            'device_id' => $device?->id,
            'created_by' => $leiding?->id,
            'assigned_to' => $technicus?->id,
            'checked_by' => $leiding?->id,
            'student_id' => $this->world,
            'created_at' => now()->subDays(2),
            'closed_at' => now()->subDays(2)->addHours(3),
        ])->saveQuietly();

        // Verwijder een eventueel bestaand plan zodat herimport er niet stapelt.
        $ticket->installationPlan()->delete();

        $plan = $ticket->installationPlan()->create([
            'werkzaamheden' => 'Koelunit monteren in U38-U40, aansluiten op PDU-B en koelwatercircuit.',
            'materialen' => 'Koelunit type DC-X2, montagerail, koelwaterslangen, PDU-aansluitkabel.',
            'middelen' => 'Steeksleutelset, momentsleutel, ladder, multimeter.',
            'betrokken_collega' => 'Jan de Vries (facilitair)',
            'security_fysiek' => 'Toegang via badge + begeleiding; ruimte afgesloten tijdens werk.',
            'security_virtueel' => 'Wijziging in change-window; monitoring tijdelijk in onderhoudsmodus.',
        ]);

        $plan->forceFill([
            'ready_at' => now()->subDays(2),
            'status' => InstallationPlanStatus::Goedgekeurd->value,
            'approved_by' => $leiding?->id,
            'approved_at' => now()->subDays(2)->addHour(),
            'created_by' => $technicus?->id,
            'student_id' => $this->world,
        ])->saveQuietly();
    }

    /**
     * Opdracht 3 & 5: internal messages incl. a customer feedback message.
     */
    protected function seedMessages(?User $technicus, ?User $leiding, ?User $klant): void
    {
        $incident = Ticket::where('number', 'INC-2026-0001')->first();

        $this->message($technicus, $leiding, $incident, 'Kun je meekijken op de latency van medicloud-db01? Lijkt koeling-gerelateerd.', now()->subMinutes(25));
        $this->message($technicus, $klant, $incident, 'Update: oorzaak gevonden (koeling). We plannen herstel in het change-window vanavond en koppelen terug zodra het opgelost is.', now()->subMinutes(10));
    }

    /**
     * Opdracht 3: an inspection report with one deviation.
     */
    protected function seedInspection(?User $technicus, ?Device $device): void
    {
        $items = [];
        foreach (InspectionReport::CONTROL_POINTS as $key => $label) {
            $items[] = [
                'key' => $key,
                'label' => $label,
                'status' => $key === 'koeling' ? InspectionStatus::Afwijking->value : InspectionStatus::Ok->value,
                'observation' => $key === 'koeling' ? 'Aanvoertemperatuur koeling te hoog, koelunit bijgeplaatst.' : '',
                'device_id' => $key === 'koeling' ? $device?->id : null,
            ];
        }

        (new InspectionReport)->forceFill([
            'inspector_id' => $technicus?->id,
            'date' => now()->subDay()->toDateString(),
            'items' => $items,
            'student_id' => $this->world,
        ])->saveQuietly();
    }

    /**
     * Docent scenario panel: a ready-to-run training scenario.
     */
    protected function seedScenario(?User $docent, ?Device $db, ?Device $app01): void
    {
        // updateOrCreate (op naam) zodat de demo veilig opnieuw geïmporteerd kan
        // worden zonder dubbele scenario's.
        Scenario::updateOrCreate(
            ['name' => 'NOC-oefening: koeling valt geleidelijk uit'],
            [
                'description' => 'Eerst een vroeg signaal (waarschuwing), daarna een echte storing.',
                'actions' => array_values(array_filter([
                    $app01 ? ['delay' => 30, 'device_id' => $app01->id, 'status' => 'waarschuwing'] : null,
                    $db ? ['delay' => 90, 'device_id' => $db->id, 'status' => 'storing'] : null,
                ])),
                'created_by' => $docent?->id,
            ],
        );

        // A provisioning scenario: builds a full starting world from scratch so
        // a docent can assign it to an empty student. The predictive device
        // (positive metric_trend) climbs each tick toward a warning.
        Scenario::updateOrCreate(
            ['name' => 'Startopstelling MediCloud (rack R03)'],
            [
                'description' => 'Bouwt een compleet rack met servers en een switch op in de wereld van de student.',
                'actions' => [
                    ['delay' => 120, 'device' => 'medicloud-db01', 'status' => 'storing'],
                ],
                'blueprint' => [
                    'rack' => ['name' => 'R03', 'location' => 'DC-Utrecht', 'height_u' => 42],
                    'devices' => [
                        ['name' => 'medicloud-app01', 'type' => 'server', 'status' => 'actief', 'u_start' => 1, 'u_end' => 2, 'cpu' => 25, 'temp' => 45, 'metric_trend' => 8],
                        ['name' => 'medicloud-app02', 'type' => 'server', 'status' => 'actief', 'u_start' => 3, 'u_end' => 4, 'cpu' => 40, 'temp' => 52, 'metric_trend' => 0],
                        ['name' => 'medicloud-db01', 'type' => 'server', 'status' => 'actief', 'u_start' => 5, 'u_end' => 8, 'cpu' => 55, 'temp' => 60, 'metric_trend' => 0],
                        ['name' => 'medicloud-sw01', 'type' => 'switch', 'status' => 'actief', 'u_start' => 10, 'u_end' => 10, 'cpu' => 10, 'temp' => 35, 'metric_trend' => 0],
                    ],
                ],
                'created_by' => $docent?->id,
            ],
        );
    }

    /**
     * Opdracht 6: one fully handled visit (in + out) and one still open.
     */
    protected function seedVisitors(?User $technicus): void
    {
        $this->visitor('Eva Engineer', 'KoelTech BV', 'Onderhoud koelunit R03', 'B-204', $technicus, now()->subHours(4), now()->subHours(1));
        $this->visitor('Pieter Pakketdienst', 'LogiTrans', 'Levering hardware', 'B-205', $technicus, now()->subMinutes(40), null);
    }

    protected function message(?User $from, ?User $to, ?Ticket $ticket, string $body, \DateTimeInterface $sentAt): void
    {
        (new Message)->forceFill([
            'from_id' => $from?->id,
            'to_id' => $to?->id,
            'ticket_id' => $ticket?->id,
            'body' => $body,
            'sent_at' => $sentAt,
            'student_id' => $this->world,
        ])->saveQuietly();
    }

    protected function visitor(string $name, string $company, string $reason, string $badge, ?User $escort, \DateTimeInterface $in, ?\DateTimeInterface $out): void
    {
        (new VisitorLog)->forceFill([
            'visitor_name' => $name,
            'company' => $company,
            'reason' => $reason,
            'badge_number' => $badge,
            'escort_id' => $escort?->id,
            'created_by' => $escort?->id,
            'checked_in_at' => $in,
            'checked_out_at' => $out,
            'student_id' => $this->world,
        ])->saveQuietly();
    }
}
