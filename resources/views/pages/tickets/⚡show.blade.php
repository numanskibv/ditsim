<?php

use App\Enums\Ability;
use App\Enums\TicketStatus;
use App\Livewire\Concerns\InteractsWithStudentWorld;
use App\Models\Ticket;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ticket')] class extends Component
{
    use InteractsWithStudentWorld;

    public Ticket $ticket;

    public ?int $checkerId = null;

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $this->checkerId = $ticket->checked_by;
    }

    /**
     * Candidate checkers: anyone other than the executor (four-eyes).
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function possibleCheckers(): Collection
    {
        return User::query()
            ->when($this->ticket->assigned_to, fn ($query) => $query->whereKeyNot($this->ticket->assigned_to))
            ->orderBy('name')
            ->get();
    }

    /**
     * Advance the ticket one step through the workflow (closing is handled
     * separately so the four-eyes rule can be enforced).
     */
    public function advanceStatus(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        $next = $this->ticket->status->next();

        if ($next === null || $next === TicketStatus::Afgesloten) {
            return;
        }

        $this->ticket->status = $next;
        $this->ticket->save();
        $this->ticket->refresh();
    }

    /**
     * Assign a checker so the ticket can later pass the four-eyes close rule.
     */
    public function assignChecker(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        $this->validate(['checkerId' => ['required', Rule::exists('users', 'id')]]);

        $this->ticket->checked_by = $this->checkerId;
        $this->ticket->save();
        $this->ticket->refresh();

        Flux::toast(variant: 'success', text: __('Controleur toegewezen.'));
    }

    /**
     * Close the ticket, enforcing the four-eyes rule with a clear message.
     */
    public function closeTicket(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        if (! $this->ticket->canBeClosed()) {
            $this->addError('close', __('Afsluiten kan alleen met een controleur die niet de uitvoerder is.'));
            Flux::toast(variant: 'danger', text: __('Afsluiten geblokkeerd: vier-ogen-controle ontbreekt.'));

            return;
        }

        $this->ticket->close();
        $this->ticket->refresh();

        Flux::toast(variant: 'success', text: __('Ticket afgesloten.'));
    }

    /**
     * Sign off the ticket as a manager (leidinggevende).
     */
    public function approve(): void
    {
        $this->authorize(Ability::ApproveTasks->value);
        $this->requireActiveStudent();

        $this->ticket->markApprovedBy(Auth::user());
        $this->ticket->refresh();

        Flux::toast(variant: 'success', text: __('Ticket afgetekend.'));
    }

    #[On('ticket-saved')]
    public function refreshTicket(): void
    {
        $this->ticket->refresh();
    }
}; ?>

<section class="w-full max-w-3xl">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:text size="sm" variant="subtle">{{ $ticket->number }}</flux:text>
            <flux:heading size="xl">{{ $ticket->title }}</flux:heading>
            @if ($ticket->student)
                <flux:text size="sm" variant="subtle">{{ __('Omgeving van') }}: {{ $ticket->student->name }}</flux:text>
            @endif
            <div class="mt-2 flex flex-wrap gap-2">
                <flux:badge size="sm">{{ $ticket->type->label() }}</flux:badge>
                <flux:badge size="sm" :color="$ticket->priority->color()">{{ $ticket->priority->label() }}</flux:badge>
                <flux:badge size="sm" :color="$ticket->status->color()">{{ $ticket->status->label() }}</flux:badge>
                <flux:badge size="sm" :color="$ticket->isWithinSla() ? 'green' : 'red'" data-test="sla-badge">
                    {{ $ticket->slaStatusLabel() }}
                </flux:badge>
            </div>
        </div>

        <div class="flex gap-2">
            @canany(['execute-tasks', 'approve-tasks'])
                <flux:button size="sm" variant="ghost" icon="clipboard-document-list" :href="route('plans.edit', $ticket)" wire:navigate>
                    {{ __('Installatieplan') }}
                </flux:button>
            @endcanany
            @canany(['create-reports', 'execute-tasks'])
                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="$dispatch('edit-ticket', { ticketId: {{ $ticket->id }} })">
                    {{ __('Bewerken') }}
                </flux:button>
            @endcanany
        </div>
    </div>

    <flux:card class="space-y-6">
        @if ($ticket->description)
            <flux:text>{{ $ticket->description }}</flux:text>
        @endif

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <flux:text variant="subtle">{{ __('Aangemaakt') }}</flux:text>
                <flux:text>{{ $ticket->created_at->format('d-m-Y H:i') }}</flux:text>
            </div>
            <div>
                <flux:text variant="subtle">{{ __('SLA-deadline') }} ({{ $ticket->sla_minutes }} min)</flux:text>
                <flux:text>{{ $ticket->slaDeadline()->format('d-m-Y H:i') }}</flux:text>
            </div>
            <div>
                <flux:text variant="subtle">{{ __('Device') }}</flux:text>
                <flux:text>{{ $ticket->device?->name ?? __('—') }}</flux:text>
            </div>
            <div>
                <flux:text variant="subtle">{{ __('Afgesloten op') }}</flux:text>
                <flux:text>{{ $ticket->closed_at?->format('d-m-Y H:i') ?? __('—') }}</flux:text>
            </div>
            <div>
                <flux:text variant="subtle">{{ __('Uitvoerder') }}</flux:text>
                <flux:text>{{ $ticket->assignee?->name ?? __('—') }}</flux:text>
            </div>
            <div>
                <flux:text variant="subtle">{{ __('Controleur') }}</flux:text>
                <flux:text>{{ $ticket->checker?->name ?? __('—') }}</flux:text>
            </div>
        </div>

        <flux:separator />

        {{-- Workflow actions for executors --}}
        @can('execute-tasks')
            <div class="space-y-4">
                <flux:heading size="sm">{{ __('Workflow') }}</flux:heading>

                @if ($ticket->status->next() && $ticket->status !== TicketStatus::WachtenOpControle)
                    <flux:button size="sm" wire:click="advanceStatus">
                        {{ __('Naar') }}: {{ $ticket->status->next()->label() }}
                    </flux:button>
                @endif

                <div class="flex items-end gap-2">
                    <flux:select wire:model="checkerId" :label="__('Controleur (vier-ogen)')" class="max-w-xs">
                        <flux:select.option :value="null">{{ __('— kies —') }}</flux:select.option>
                        @foreach ($this->possibleCheckers as $candidate)
                            <flux:select.option :value="$candidate->id">{{ $candidate->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button size="sm" variant="subtle" wire:click="assignChecker">{{ __('Toewijzen') }}</flux:button>
                </div>

                @if ($ticket->status === TicketStatus::WachtenOpControle)
                    @if ($ticket->canBeClosed())
                        <flux:button variant="primary" icon="check" wire:click="closeTicket" data-test="close-ticket-button">
                            {{ __('Ticket afsluiten') }}
                        </flux:button>
                    @else
                        <flux:callout variant="warning" icon="shield-exclamation" data-test="four-eyes-warning">
                            <flux:callout.heading>{{ __('Afsluiten geblokkeerd') }}</flux:callout.heading>
                            <flux:callout.text>
                                {{ __('Vier-ogen-principe: wijs eerst een controleur aan die niet de uitvoerder is. Daarna kan het ticket worden afgesloten.') }}
                            </flux:callout.text>
                        </flux:callout>
                    @endif
                @endif

                @error('close')
                    <flux:text color="red">{{ $message }}</flux:text>
                @enderror
            </div>
        @endcan

        {{-- Sign-off for managers --}}
        @can('approve-tasks')
            <flux:separator />
            <div class="space-y-2">
                <flux:heading size="sm">{{ __('Aftekenen (leidinggevende)') }}</flux:heading>
                @if ($ticket->approved_at)
                    <flux:text data-test="approved-info">
                        {{ __('Afgetekend door') }} <span class="font-medium">{{ $ticket->approver?->name }}</span>
                        · {{ $ticket->approved_at->format('d-m-Y H:i') }}
                    </flux:text>
                @else
                    <flux:button variant="primary" icon="check-badge" wire:click="approve" data-test="approve-button">
                        {{ __('Ticket aftekenen') }}
                    </flux:button>
                @endif
            </div>
        @endcan
    </flux:card>

    <flux:card>
        <livewire:messages.ticket-thread :ticket="$ticket" :wire:key="'thread-'.$ticket->id" />
    </flux:card>

    <div class="mt-4">
        <flux:link :href="route('tickets.index')" wire:navigate>← {{ __('Terug naar tickets') }}</flux:link>
    </div>

    <livewire:tickets.ticket-form />
</section>
