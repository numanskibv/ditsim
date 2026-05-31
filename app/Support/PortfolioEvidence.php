<?php

namespace App\Support;

use App\Enums\PortfolioAssignment;
use App\Models\DeviceAlert;
use App\Models\InspectionReport;
use App\Models\Message;
use App\Models\Rack;
use App\Models\Ticket;
use App\Models\VisitorLog;
use Illuminate\Support\Collection;

class PortfolioEvidence
{
    /**
     * Gather the real evidence for an assignment plus a list of any missing
     * mandatory parts. Nothing is fabricated: every section comes from the
     * actual module data.
     *
     * @return array{missing: list<string>, data: array<string, mixed>}
     */
    public function gather(PortfolioAssignment $assignment, ?Ticket $ticket = null): array
    {
        return match ($assignment) {
            PortfolioAssignment::One => $this->forInstallation($ticket),
            PortfolioAssignment::Two => $this->forMonitoring(),
            PortfolioAssignment::Three => $this->forInspection(),
            PortfolioAssignment::Four => $this->forRepair($ticket),
            PortfolioAssignment::Five => $this->forIncident($ticket),
            PortfolioAssignment::Six => $this->forAccess(),
        };
    }

    /**
     * Opdracht 1: ticket + goedgekeurd installatieplan + DCIM-stand.
     *
     * @return array{missing: list<string>, data: array<string, mixed>}
     */
    protected function forInstallation(?Ticket $ticket): array
    {
        $ticket ??= Ticket::query()
            ->whereHas('installationPlan', fn ($query) => $query->where('status', 'goedgekeurd'))
            ->latest()
            ->first();

        $plan = $ticket?->installationPlan;
        $missing = [];

        if (! $ticket) {
            $missing[] = 'Geen ticket geselecteerd of gevonden.';
        }

        if (! $plan || ! $plan->isApproved()) {
            $missing[] = 'Een goedgekeurd installatieplan ontbreekt.';
        }

        return [
            'missing' => $missing,
            'data' => ['ticket' => $ticket, 'plan' => $plan?->loadMissing('approver'), 'racks' => $this->dcim()],
        ];
    }

    /**
     * Opdracht 2: incidentlog/monitoringverslag + tickets.
     *
     * @return array{missing: list<string>, data: array<string, mixed>}
     */
    protected function forMonitoring(): array
    {
        $alerts = DeviceAlert::with('device')->latest()->limit(25)->get();
        $tickets = Ticket::where('type', 'incident')->latest()->get();
        $missing = [];

        if ($alerts->isEmpty()) {
            $missing[] = 'Geen incident-/monitoringmeldingen aanwezig.';
        }

        if ($tickets->isEmpty()) {
            $missing[] = 'Geen incidenttickets aanwezig.';
        }

        return [
            'missing' => $missing,
            'data' => ['alerts' => $alerts, 'tickets' => $tickets],
        ];
    }

    /**
     * Opdracht 3: inspectierapport + communicatiebericht.
     *
     * @return array{missing: list<string>, data: array<string, mixed>}
     */
    protected function forInspection(): array
    {
        $inspection = InspectionReport::with('inspector')->latest()->first();
        $message = Message::with(['sender', 'recipient', 'ticket'])->latest('sent_at')->first();
        $missing = [];

        if (! $inspection) {
            $missing[] = 'Geen inspectierapport aanwezig.';
        }

        if (! $message) {
            $missing[] = 'Geen communicatiebericht aanwezig.';
        }

        return [
            'missing' => $missing,
            'data' => ['inspection' => $inspection, 'message' => $message],
        ];
    }

    /**
     * Opdracht 4: reparatie-ticket (vroeg signaal → actie → controle → resultaat).
     *
     * @return array{missing: list<string>, data: array<string, mixed>}
     */
    protected function forRepair(?Ticket $ticket): array
    {
        $ticket ??= Ticket::query()
            ->whereNotNull('checked_by')
            ->whereNotNull('closed_at')
            ->latest()
            ->first();

        $missing = [];

        if (! $ticket) {
            $missing[] = 'Geen afgerond reparatieticket geselecteerd of gevonden.';
        } else {
            if (! $ticket->checked_by) {
                $missing[] = 'Controle (vier-ogen) ontbreekt op het ticket.';
            }

            if (! $ticket->closed_at) {
                $missing[] = 'Resultaat ontbreekt: het ticket is nog niet afgesloten.';
            }
        }

        $alerts = $ticket?->device
            ? $ticket->device->alerts()->oldest()->get()
            : new Collection;

        return [
            'missing' => $missing,
            'data' => ['ticket' => $ticket?->loadMissing(['assignee', 'checker', 'device']), 'alerts' => $alerts],
        ];
    }

    /**
     * Opdracht 5: ticket + DCIM-stand + terugkoppelingsbericht.
     *
     * @return array{missing: list<string>, data: array<string, mixed>}
     */
    protected function forIncident(?Ticket $ticket): array
    {
        $ticket ??= Ticket::query()->whereHas('messages')->latest()->first();
        $missing = [];

        if (! $ticket) {
            $missing[] = 'Geen ticket geselecteerd of gevonden.';
        }

        $feedback = $ticket?->messages()->with(['sender', 'recipient'])->latest('sent_at')->first();

        if (! $feedback) {
            $missing[] = 'Terugkoppelingsbericht ontbreekt.';
        }

        return [
            'missing' => $missing,
            'data' => ['ticket' => $ticket, 'racks' => $this->dcim(), 'feedback' => $feedback],
        ];
    }

    /**
     * Opdracht 6: begeleidingsverslag + toegangsregister (aan+afmelden) + DCIM-stand.
     *
     * @return array{missing: list<string>, data: array<string, mixed>}
     */
    protected function forAccess(): array
    {
        $visit = VisitorLog::with('escort')
            ->whereNotNull('checked_in_at')
            ->whereNotNull('checked_out_at')
            ->latest()
            ->first();

        $openVisits = VisitorLog::whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->get();

        $missing = [];

        if (! $visit) {
            $missing[] = 'Geen volledig afgehandelde bezoekersregistratie (aan- én afmelding) aanwezig.';
        }

        return [
            'missing' => $missing,
            'data' => ['visit' => $visit, 'openVisits' => $openVisits, 'racks' => $this->dcim()],
        ];
    }

    /**
     * The current DCIM state (racks with their devices).
     *
     * @return Collection<int, Rack>
     */
    protected function dcim(): Collection
    {
        return Rack::with('devices')->orderBy('name')->get();
    }
}
