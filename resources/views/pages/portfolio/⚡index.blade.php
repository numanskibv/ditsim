<?php

use App\Enums\PortfolioAssignment;
use App\Models\Ticket;
use App\Support\PortfolioEvidence;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Portfoliobewijs')] class extends Component
{
    public ?int $ticketId = null;

    /**
     * @return array<int, PortfolioAssignment>
     */
    #[Computed]
    public function assignments(): array
    {
        return PortfolioAssignment::cases();
    }

    /**
     * @return Collection<int, Ticket>
     */
    #[Computed]
    public function tickets(): Collection
    {
        return Ticket::latest()->get();
    }

    /**
     * Missing mandatory parts per assignment, given the selected ticket.
     *
     * @return array<int, list<string>>
     */
    #[Computed]
    public function status(): array
    {
        $evidence = app(PortfolioEvidence::class);
        $ticket = $this->ticketId ? Ticket::find($this->ticketId) : null;

        $status = [];
        foreach (PortfolioAssignment::cases() as $assignment) {
            $context = $assignment->requiresTicket() ? $ticket : null;
            $status[$assignment->value] = $evidence->gather($assignment, $context)['missing'];
        }

        return $status;
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Portfoliobewijs') }}</flux:heading>
        <flux:subheading>{{ __('Genereer per opdracht een kant-en-klare PDF met de echte bewijsstukken.') }}</flux:subheading>
    </div>

    <flux:select wire:model.live="ticketId" :label="__('Ticket (voor opdracht 1, 4 en 5)')" class="max-w-md">
        <flux:select.option :value="null">{{ __('— automatisch kiezen —') }}</flux:select.option>
        @foreach ($this->tickets as $ticket)
            <flux:select.option :value="$ticket->id">{{ $ticket->number }} — {{ $ticket->title }}</flux:select.option>
        @endforeach
    </flux:select>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($this->assignments as $assignment)
            @php($missing = $this->status[$assignment->value])
            <flux:card wire:key="assignment-{{ $assignment->value }}" class="space-y-3">
                <div>
                    <flux:heading size="lg">{{ __('Opdracht') }} {{ $assignment->value }}</flux:heading>
                    <flux:text variant="strong">{{ $assignment->title() }}</flux:text>
                    <flux:text size="sm" variant="subtle">{{ __('Werkproces') }}: {{ $assignment->werkproces() }}</flux:text>
                </div>

                @if (count($missing) > 0)
                    <flux:callout variant="warning" icon="exclamation-triangle" data-test="missing-{{ $assignment->value }}">
                        <flux:callout.heading>{{ __('Bewijs onvolledig') }}</flux:callout.heading>
                        <flux:callout.text>
                            <ul class="list-disc ps-4">
                                @foreach ($missing as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </flux:callout.text>
                    </flux:callout>
                    <flux:button variant="ghost" icon="document-arrow-down" disabled>
                        {{ __('Genereer portfoliobewijs') }}
                    </flux:button>
                @else
                    <flux:button
                        variant="primary"
                        icon="document-arrow-down"
                        :href="route('portfolio.pdf', ['assignment' => $assignment->value, 'ticket' => $this->ticketId])"
                        target="_blank"
                        data-test="generate-{{ $assignment->value }}"
                    >
                        {{ __('Genereer portfoliobewijs') }}
                    </flux:button>
                @endif
            </flux:card>
        @endforeach
    </div>
</section>
