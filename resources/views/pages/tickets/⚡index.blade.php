<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Tickets')] class extends Component
{
    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $priorityFilter = '';

    /**
     * The filtered list of tickets for the table.
     *
     * @return Collection<int, Ticket>
     */
    #[Computed]
    public function tickets(): Collection
    {
        return Ticket::with(['assignee', 'device', 'student'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== '', fn ($query) => $query->where('priority', $this->priorityFilter))
            ->latest()
            ->get();
    }

    /**
     * @return array<int, TicketStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return TicketStatus::cases();
    }

    /**
     * @return array<int, TicketPriority>
     */
    #[Computed]
    public function priorities(): array
    {
        return TicketPriority::cases();
    }

    #[On('ticket-saved')]
    public function refresh(): void
    {
        unset($this->tickets);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Tickets') }}</flux:heading>
            <flux:subheading>{{ __('Incidenten, wijzigingen en serviceverzoeken met SLA-bewaking.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="$dispatch('create-ticket')">
            {{ __('Nieuw ticket') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-wrap gap-4">
        <flux:select wire:model.live="statusFilter" :label="__('Status')" class="max-w-xs">
            <flux:select.option value="">{{ __('Alle statussen') }}</flux:select.option>
            @foreach ($this->statuses as $status)
                <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="priorityFilter" :label="__('Prioriteit')" class="max-w-xs">
            <flux:select.option value="">{{ __('Alle prioriteiten') }}</flux:select.option>
            @foreach ($this->priorities as $priority)
                <flux:select.option :value="$priority->value">{{ $priority->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Nummer') }}</flux:table.column>
            <flux:table.column>{{ __('Titel') }}</flux:table.column>
            <flux:table.column>{{ __('Prioriteit') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('SLA') }}</flux:table.column>
            <flux:table.column>{{ __('Toegewezen') }}</flux:table.column>
            <flux:table.column>{{ __('Student') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->tickets as $ticket)
                <flux:table.row wire:key="ticket-{{ $ticket->id }}">
                    <flux:table.cell>
                        <flux:link :href="route('tickets.show', $ticket)" wire:navigate>{{ $ticket->number }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $ticket->title }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$ticket->priority->color()">{{ $ticket->priority->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$ticket->status->color()">{{ $ticket->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$ticket->isWithinSla() ? 'green' : 'red'">
                            {{ $ticket->slaStatusLabel() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $ticket->assignee?->name ?? __('—') }}</flux:table.cell>
                    <flux:table.cell>{{ $ticket->student?->name ?? __('—') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ __('Geen tickets gevonden.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:tickets.ticket-form />
</section>
