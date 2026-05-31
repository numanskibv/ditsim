<?php

use App\Enums\Ability;
use App\Enums\InstallationPlanStatus;
use App\Livewire\Concerns\InteractsWithStudentWorld;
use App\Models\InstallationPlan;
use App\Models\Ticket;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Installatieplan')] class extends Component
{
    use InteractsWithStudentWorld;

    public Ticket $ticket;

    public ?InstallationPlan $plan = null;

    public string $werkzaamheden = '';

    public string $materialen = '';

    public string $middelen = '';

    public string $betrokken_collega = '';

    public string $security_fysiek = '';

    public string $security_virtueel = '';

    public string $rejectReason = '';

    public function mount(Ticket $ticket): void
    {
        abort_unless(
            Gate::any([Ability::ExecuteTasks->value, Ability::ApproveTasks->value]),
            403,
        );

        $this->ticket = $ticket;
        $this->plan = $ticket->installationPlan;

        if ($this->plan) {
            foreach (array_keys(InstallationPlan::REQUIRED_FIELDS) as $field) {
                $this->{$field} = (string) $this->plan->{$field};
            }
        }
    }

    /**
     * Save the current sections as a draft (no completeness requirement).
     */
    public function savePlan(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        $this->persist();

        Flux::toast(variant: 'success', text: __('Concept opgeslagen.'));
    }

    /**
     * Mark the plan ready. Every mandatory section must be filled, otherwise
     * the UI blocks it with a clear per-section message.
     */
    public function markReady(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        $this->validate(
            rules: array_fill_keys(
                array_map(fn (string $field): string => $field, array_keys(InstallationPlan::REQUIRED_FIELDS)),
                ['required', 'string'],
            ),
            attributes: InstallationPlan::REQUIRED_FIELDS,
        );

        $this->persist()->markReady();

        Flux::toast(variant: 'success', text: __('Plan gemarkeerd als klaar voor goedkeuring.'));
    }

    public function approve(): void
    {
        $this->authorize(Ability::ApproveTasks->value);
        $this->requireActiveStudent();

        $this->plan?->approveBy(Auth::user());
        $this->plan?->refresh();

        Flux::toast(variant: 'success', text: __('Plan goedgekeurd.'));
    }

    public function reject(): void
    {
        $this->authorize(Ability::ApproveTasks->value);
        $this->requireActiveStudent();

        $this->validate(['rejectReason' => ['required', 'string', 'min:3']]);

        $this->plan?->rejectBy(Auth::user(), $this->rejectReason);
        $this->plan?->refresh();
        $this->reset('rejectReason');

        Flux::toast(variant: 'warning', text: __('Plan afgekeurd.'));
    }

    /**
     * Create or update the plan from the bound section fields.
     */
    protected function persist(): InstallationPlan
    {
        $data = [];
        foreach (array_keys(InstallationPlan::REQUIRED_FIELDS) as $field) {
            $data[$field] = $this->{$field};
        }

        if ($this->plan) {
            $this->plan->update($data);
        } else {
            $this->plan = $this->ticket->installationPlan()->create([
                ...$data,
                'created_by' => Auth::id(),
            ]);
        }

        return $this->plan;
    }
}; ?>

<section class="w-full max-w-3xl space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <flux:text size="sm" variant="subtle">{{ $ticket->number }} — {{ $ticket->title }}</flux:text>
            <flux:heading size="xl">{{ __('Installatieplan') }}</flux:heading>
            @if ($ticket->student)
                <flux:text size="sm" variant="subtle">{{ __('Omgeving van') }}: {{ $ticket->student->name }}</flux:text>
            @endif
        </div>
        @if ($plan)
            <flux:badge :color="$plan->status->color()">{{ $plan->status->label() }}</flux:badge>
        @endif
    </div>

    @if ($plan?->status === InstallationPlanStatus::Afgekeurd && $plan->rejection_reason)
        <flux:callout variant="danger" icon="x-circle">
            <flux:callout.heading>{{ __('Afgekeurd') }}</flux:callout.heading>
            <flux:callout.text>{{ $plan->rejection_reason }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Section form (editable while not yet approved) --}}
    @php($locked = $plan?->isApproved())
    <flux:card class="space-y-5">
        <flux:textarea wire:model="werkzaamheden" :label="__('Werkzaamheden')" rows="3" :disabled="$locked" />
        <flux:textarea wire:model="materialen" :label="__('Materialenlijst')" rows="3" :disabled="$locked" />
        <flux:textarea wire:model="middelen" :label="__('Middelenlijst')" rows="3" :disabled="$locked" />
        <flux:input wire:model="betrokken_collega" :label="__('Betrokken collega')" :disabled="$locked" />
        <flux:textarea wire:model="security_fysiek" :label="__('Securitymaatregelen (fysiek)')" rows="2" :disabled="$locked" />
        <flux:textarea wire:model="security_virtueel" :label="__('Securitymaatregelen (virtueel)')" rows="2" :disabled="$locked" />

        @can('execute-tasks')
            @unless ($locked)
                <div class="flex gap-2">
                    <flux:button variant="ghost" wire:click="savePlan">{{ __('Concept opslaan') }}</flux:button>
                    <flux:button variant="primary" wire:click="markReady" data-test="mark-ready-button">
                        {{ __('Markeer als klaar') }}
                    </flux:button>
                </div>
            @endunless
        @endcan
    </flux:card>

    {{-- Manager approval --}}
    @can('approve-tasks')
        @if ($plan && $plan->ready_at && $plan->status === InstallationPlanStatus::Concept)
            <flux:card class="space-y-3">
                <flux:heading size="sm">{{ __('Goedkeuring (leidinggevende)') }}</flux:heading>
                <div class="flex gap-2">
                    <flux:button variant="primary" icon="check" wire:click="approve" data-test="approve-plan-button">
                        {{ __('Goedkeuren') }}
                    </flux:button>
                </div>
                <flux:input wire:model="rejectReason" :label="__('Reden van afkeuring')" />
                <flux:button variant="danger" icon="x-mark" wire:click="reject" data-test="reject-plan-button">
                    {{ __('Afkeuren') }}
                </flux:button>
            </flux:card>
        @endif
    @endcan

    {{-- Approved: show sign-off + PDF download --}}
    @if ($plan?->isApproved())
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.heading>
                {{ __('Goedgekeurd door') }} {{ $plan->approver?->name }} {{ __('op') }} {{ $plan->approved_at->format('d-m-Y H:i') }}
            </flux:callout.heading>
            <flux:callout.text>
                <flux:button :href="route('plans.pdf', $ticket)" icon="arrow-down-tray" variant="primary" target="_blank">
                    {{ __('Download PDF') }}
                </flux:button>
            </flux:callout.text>
        </flux:callout>
    @endif

    <flux:link :href="route('tickets.show', $ticket)" wire:navigate>← {{ __('Terug naar ticket') }}</flux:link>
</section>
