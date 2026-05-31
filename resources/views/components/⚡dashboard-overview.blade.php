<?php

use App\Enums\DeviceStatus;
use App\Enums\InstallationPlanStatus;
use App\Enums\PortfolioAssignment;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Device;
use App\Models\InstallationPlan;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VisitorLog;
use App\Models\Scopes\StudentScope;
use App\Support\CurrentStudent;
use App\Support\PortfolioEvidence;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Role-aware dashboard: each role lands on a "where do I stand / what to do
 * next" overview, scoped to the world currently in view.
 */
new class extends Component
{
    #[Computed]
    public function role(): Role
    {
        return auth()->user()->role;
    }

    // ---- Technicus (own world) -------------------------------------------

    /**
     * Per-assignment completeness for the portfolio progress widget.
     *
     * @return list<array{assignment: PortfolioAssignment, complete: bool}>
     */
    #[Computed]
    public function portfolio(): array
    {
        $evidence = app(PortfolioEvidence::class);

        return collect(PortfolioAssignment::cases())
            ->map(fn (PortfolioAssignment $a): array => [
                'assignment' => $a,
                'complete' => $evidence->gather($a)['missing'] === [],
            ])
            ->all();
    }

    public function completedCount(): int
    {
        return collect($this->portfolio)->filter(fn (array $row): bool => $row['complete'])->count();
    }

    /**
     * @return Collection<int, Ticket>
     */
    #[Computed]
    public function myTickets(): Collection
    {
        return Ticket::with('device')
            ->where('status', '!=', TicketStatus::Afgesloten->value)
            ->latest()
            ->limit(6)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function deviceCounts(): array
    {
        return Device::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();
    }

    #[Computed]
    public function openVisits(): int
    {
        return VisitorLog::whereNotNull('checked_in_at')->whereNull('checked_out_at')->count();
    }

    // ---- Docent (across all worlds) --------------------------------------

    /**
     * @return Collection<int, array{student: User, complete: int, devices: int, faults: int}>
     */
    #[Computed]
    public function classRows(): Collection
    {
        $evidence = app(PortfolioEvidence::class);
        $resolver = app(CurrentStudent::class);

        return User::where('role', Role::Technicus->value)
            ->with(['partner', 'assignedScenario'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $student): array => $resolver->runFor($student->id, fn (): array => [
                'student' => $student,
                'complete' => collect(PortfolioAssignment::cases())
                    ->filter(fn (PortfolioAssignment $a): bool => $evidence->gather($a)['missing'] === [])
                    ->count(),
                'devices' => Device::count(),
                'faults' => Device::where('status', DeviceStatus::Storing->value)->count(),
            ]));
    }

    // ---- Leidinggevende (to-do across worlds) ----------------------------

    /**
     * @return Collection<int, InstallationPlan>
     */
    #[Computed]
    public function plansToReview(): Collection
    {
        return InstallationPlan::withoutGlobalScope(StudentScope::class)
            ->whereNotNull('ready_at')
            ->where('status', InstallationPlanStatus::Concept->value)
            ->with(['ticket', 'student'])
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, Ticket>
     */
    #[Computed]
    public function ticketsToApprove(): Collection
    {
        return Ticket::withoutGlobalScope(StudentScope::class)
            ->where('status', TicketStatus::WachtenOpControle->value)
            ->whereNull('approved_by')
            ->with('student')
            ->latest()
            ->get();
    }

    // ---- Klant -----------------------------------------------------------

    /**
     * @return Collection<int, Ticket>
     */
    #[Computed]
    public function myReports(): Collection
    {
        return Ticket::withoutGlobalScope(StudentScope::class)
            ->where('created_by', auth()->id())
            ->with('student')
            ->latest()
            ->limit(10)
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Welkom, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:subheading>{{ $this->role->label() }}</flux:subheading>
    </div>

    {{-- ============================ TECHNICUS ============================ --}}
    @if ($this->role === Role::Technicus)
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Mijn examenvoortgang') }}</flux:heading>
                <flux:badge :color="$this->completedCount() === 6 ? 'green' : 'amber'">
                    {{ $this->completedCount() }}/6 {{ __('compleet') }}
                </flux:badge>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($this->portfolio as $row)
                    <div class="flex items-center gap-2 rounded-lg border border-zinc-100 p-2 dark:border-zinc-800" wire:key="opdr-{{ $row['assignment']->value }}">
                        <flux:icon :name="$row['complete'] ? 'check-circle' : 'minus-circle'" class="{{ $row['complete'] ? 'text-green-500' : 'text-zinc-400' }} size-5 shrink-0" />
                        <flux:text size="sm">{{ __('Opdracht') }} {{ $row['assignment']->value }}: {{ $row['assignment']->title() }}</flux:text>
                    </div>
                @endforeach
            </div>

            <flux:button size="sm" variant="primary" icon="document-arrow-down" :href="route('portfolio.index')" wire:navigate>
                {{ __('Naar Portfoliobewijs') }}
            </flux:button>
        </flux:card>

        <div class="grid gap-6 lg:grid-cols-2">
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('Mijn openstaande tickets') }}</flux:heading>
                @forelse ($this->myTickets as $ticket)
                    <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-2 last:border-b-0 dark:border-zinc-800" wire:key="tic-{{ $ticket->id }}">
                        <div class="min-w-0">
                            <flux:link :href="route('tickets.show', $ticket)" wire:navigate>{{ $ticket->number }}</flux:link>
                            <flux:text size="sm" variant="subtle" class="truncate">{{ $ticket->title }}</flux:text>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <flux:badge size="sm" :color="$ticket->status->color()">{{ $ticket->status->label() }}</flux:badge>
                            <flux:badge size="sm" :color="$ticket->isWithinSla() ? 'green' : 'red'">{{ $ticket->slaStatusLabel() }}</flux:badge>
                        </div>
                    </div>
                @empty
                    <flux:text size="sm" variant="subtle">{{ __('Geen openstaande tickets.') }}</flux:text>
                @endforelse
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('Mijn omgeving') }}</flux:heading>
                <div class="flex flex-wrap gap-2">
                    @foreach (DeviceStatus::cases() as $status)
                        <flux:badge :color="$status->color()">{{ $status->label() }}: {{ $this->deviceCounts[$status->value] ?? 0 }}</flux:badge>
                    @endforeach
                </div>
                @if ($this->openVisits > 0)
                    <flux:text size="sm" class="text-amber-600 dark:text-amber-400">
                        {{ __('Let op: :n bezoeker(s) nog niet afgemeld.', ['n' => $this->openVisits]) }}
                    </flux:text>
                @endif
                <div class="flex flex-wrap gap-2">
                    <flux:button size="sm" variant="filled" icon="signal" :href="route('monitoring')" wire:navigate>{{ __('Monitoring') }}</flux:button>
                    <flux:button size="sm" variant="filled" icon="clipboard-document-check" :href="route('inspections.index')" wire:navigate>{{ __('Inspectieronde') }}</flux:button>
                    <flux:button size="sm" variant="filled" icon="identification" :href="route('access.index')" wire:navigate>{{ __('Toegangsregister') }}</flux:button>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- ============================ DOCENT =============================== --}}
    @if ($this->role === Role::Docent)
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Klasoverzicht') }}</flux:heading>
                <flux:button size="sm" variant="primary" icon="academic-cap" :href="route('students.index')" wire:navigate>{{ __('Studenten beheren') }}</flux:button>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Student') }}</flux:table.column>
                    <flux:table.column>{{ __('Voortgang') }}</flux:table.column>
                    <flux:table.column>{{ __('Devices') }}</flux:table.column>
                    <flux:table.column>{{ __('Storingen') }}</flux:table.column>
                    <flux:table.column>{{ __('Scenario') }}</flux:table.column>
                    <flux:table.column>{{ __('Partner') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->classRows as $row)
                        <flux:table.row wire:key="cls-{{ $row['student']->id }}">
                            <flux:table.cell>{{ $row['student']->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$row['complete'] === 6 ? 'green' : ($row['complete'] === 0 ? 'zinc' : 'amber')">{{ $row['complete'] }}/6</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row['devices'] }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($row['faults'] > 0)
                                    <flux:badge size="sm" color="red">{{ $row['faults'] }}</flux:badge>
                                @else
                                    <flux:text size="sm" variant="subtle">—</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row['student']->assignedScenario?->name ?? __('—') }}</flux:table.cell>
                            <flux:table.cell>{{ $row['student']->partner?->name ?? __('geen') }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">{{ __('Nog geen studenten.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- ======================== LEIDINGGEVENDE ========================== --}}
    @if ($this->role === Role::Leidinggevende)
        <div class="grid gap-6 lg:grid-cols-2">
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('Plannen te beoordelen') }}</flux:heading>
                @forelse ($this->plansToReview as $plan)
                    <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-2 last:border-b-0 dark:border-zinc-800" wire:key="plan-{{ $plan->id }}">
                        <div class="min-w-0">
                            <flux:link :href="route('plans.edit', $plan->ticket)" wire:navigate>{{ $plan->ticket?->number }}</flux:link>
                            <flux:text size="sm" variant="subtle">{{ __('Omgeving van') }}: {{ $plan->student?->name ?? '—' }}</flux:text>
                        </div>
                        <flux:badge size="sm" color="amber">{{ __('gereed') }}</flux:badge>
                    </div>
                @empty
                    <flux:text size="sm" variant="subtle">{{ __('Geen plannen die op goedkeuring wachten.') }}</flux:text>
                @endforelse
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('Tickets te controleren') }}</flux:heading>
                @forelse ($this->ticketsToApprove as $ticket)
                    <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-2 last:border-b-0 dark:border-zinc-800" wire:key="appr-{{ $ticket->id }}">
                        <div class="min-w-0">
                            <flux:link :href="route('tickets.show', $ticket)" wire:navigate>{{ $ticket->number }}</flux:link>
                            <flux:text size="sm" variant="subtle">{{ __('Omgeving van') }}: {{ $ticket->student?->name ?? '—' }}</flux:text>
                        </div>
                        <flux:badge size="sm" :color="$ticket->isWithinSla() ? 'green' : 'red'">{{ $ticket->slaStatusLabel() }}</flux:badge>
                    </div>
                @empty
                    <flux:text size="sm" variant="subtle">{{ __('Geen tickets die op aftekening wachten.') }}</flux:text>
                @endforelse
            </flux:card>
        </div>
        <flux:text size="sm" variant="subtle">
            {{ __('Tip: kies bovenin een Actieve student om in die omgeving goed te keuren.') }}
        </flux:text>
    @endif

    {{-- ============================= KLANT ============================== --}}
    @if ($this->role === Role::Klant)
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Mijn meldingen') }}</flux:heading>
                <flux:button size="sm" variant="primary" icon="exclamation-triangle" :href="route('tickets.index')" wire:navigate>{{ __('Melding maken') }}</flux:button>
            </div>
            @forelse ($this->myReports as $ticket)
                <div class="flex items-center justify-between gap-2 border-b border-zinc-100 pb-2 last:border-b-0 dark:border-zinc-800" wire:key="rep-{{ $ticket->id }}">
                    <div class="min-w-0">
                        <flux:link :href="route('tickets.show', $ticket)" wire:navigate>{{ $ticket->number }}</flux:link>
                        <flux:text size="sm" variant="subtle" class="truncate">{{ $ticket->title }}</flux:text>
                    </div>
                    <flux:badge size="sm" :color="$ticket->status->color()">{{ $ticket->status->label() }}</flux:badge>
                </div>
            @empty
                <flux:text size="sm" variant="subtle">{{ __('Je hebt nog geen meldingen gemaakt.') }}</flux:text>
            @endforelse
        </flux:card>
    @endif
</div>
