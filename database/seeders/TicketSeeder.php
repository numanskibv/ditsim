<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Device;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo tickets for the MediCloud context, covering the open
     * workflow, an SLA breach and a four-eyes closed-and-approved ticket.
     */
    /**
     * The student world the demo tickets belong to.
     */
    private ?int $world = null;

    public function run(): void
    {
        $klant = User::where('email', 'klant@medicloud.test')->first();
        $technicus = User::where('role', Role::Technicus->value)->first();
        $leidinggevende = User::where('role', Role::Leidinggevende->value)->first();

        $this->world = $technicus?->id;

        $db = Device::where('name', 'medicloud-db01')->first();
        $switch = Device::where('name', 'medicloud-sw01')->first();

        // P1 incident still awaiting a checker — cannot be closed yet.
        $this->makeTicket([
            'number' => 'INC-2026-0001',
            'type' => TicketType::Incident,
            'title' => 'Database medicloud-db01 reageert traag',
            'description' => 'Hoge latency op de primaire database.',
            'status' => TicketStatus::WachtenOpControle,
            'priority' => TicketPriority::P1,
            'device_id' => $db?->id,
            'created_by' => $klant?->id,
            'assigned_to' => $technicus?->id,
            'created_at' => now()->subMinutes(30),
        ]);

        // P2 incident closed within SLA, checked and signed off (four-eyes).
        $createdAt = now()->subMinutes(120);
        $this->makeTicket([
            'number' => 'INC-2026-0002',
            'type' => TicketType::Incident,
            'title' => 'Netwerkpoort medicloud-sw01 geflapt',
            'description' => 'Tijdelijke uitval, poort gereset.',
            'status' => TicketStatus::Afgesloten,
            'priority' => TicketPriority::P2,
            'device_id' => $switch?->id,
            'created_by' => $klant?->id,
            'assigned_to' => $technicus?->id,
            'checked_by' => $leidinggevende?->id,
            'created_at' => $createdAt,
            'closed_at' => $createdAt->addMinutes(90),
            'approved_by' => $leidinggevende?->id,
            'approved_at' => $createdAt->addMinutes(95),
        ]);

        // P3 service request closed too late — outside SLA.
        $lateCreatedAt = now()->subMinutes(600);
        $this->makeTicket([
            'number' => 'SR-2026-0001',
            'type' => TicketType::ServiceRequest,
            'title' => 'Extra opslag aanvragen voor MediCloud',
            'description' => 'Verzoek tot uitbreiding van de opslagcapaciteit.',
            'status' => TicketStatus::Afgesloten,
            'priority' => TicketPriority::P3,
            'created_by' => $klant?->id,
            'assigned_to' => $technicus?->id,
            'checked_by' => $leidinggevende?->id,
            'created_at' => $lateCreatedAt,
            'closed_at' => now(),
        ]);
    }

    /**
     * Persist a ticket with explicit attributes, bypassing the lifecycle
     * hooks that are disabled during seeding.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeTicket(array $attributes): void
    {
        $priority = $attributes['priority'];
        $attributes['sla_minutes'] = $priority->slaMinutes();
        $attributes['student_id'] ??= $this->world;

        (new Ticket)->forceFill($attributes)->saveQuietly();
    }
}
