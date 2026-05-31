<?php

use App\Livewire\Concerns\InteractsWithStudentWorld;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Berichten')] class extends Component
{
    use InteractsWithStudentWorld;

    public ?int $to_id = null;

    public ?int $ticket_id = null;

    public string $body = '';

    /**
     * Possible recipients (every other user — colleague or reporter).
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function recipients(): Collection
    {
        return User::whereKeyNot(Auth::id())->orderBy('name')->get();
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
     * The current user's personal timeline (sent and received).
     *
     * @return Collection<int, Message>
     */
    #[Computed]
    public function feed(): Collection
    {
        return Message::with(['sender', 'recipient', 'ticket'])
            ->forUser(Auth::id())
            ->latest('sent_at')
            ->get();
    }

    public function send(): void
    {
        $this->requireActiveStudent();

        $validated = $this->validate([
            'to_id' => ['required', Rule::exists('users', 'id'), Rule::notIn([Auth::id()])],
            'ticket_id' => ['nullable', Rule::exists('tickets', 'id')],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Message::create($validated);

        $this->reset('body');

        Flux::toast(variant: 'success', text: __('Bericht verstuurd.'));
    }
}; ?>

<section class="w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Berichten') }}</flux:heading>
        <flux:subheading>{{ __('Stuur een collega of de melder een bericht, optioneel gekoppeld aan een ticket.') }}</flux:subheading>
    </div>

    <flux:card class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="to_id" :label="__('Aan')" required>
                <flux:select.option :value="null">{{ __('— kies ontvanger —') }}</flux:select.option>
                @foreach ($this->recipients as $recipient)
                    <flux:select.option :value="$recipient->id">{{ $recipient->name }} ({{ $recipient->role->label() }})</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="ticket_id" :label="__('Koppel aan ticket (optioneel)')">
                <flux:select.option :value="null">{{ __('— geen —') }}</flux:select.option>
                @foreach ($this->tickets as $ticket)
                    <flux:select.option :value="$ticket->id">{{ $ticket->number }} — {{ $ticket->title }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="body" :label="__('Bericht')" rows="3" required />
        @error('to_id') <flux:text color="red">{{ $message }}</flux:text> @enderror

        <flux:button variant="primary" icon="paper-airplane" wire:click="send" data-test="send-message-button">
            {{ __('Versturen') }}
        </flux:button>
    </flux:card>

    <flux:card class="space-y-3">
        <flux:heading size="lg">{{ __('Mijn tijdlijn') }}</flux:heading>
        @forelse ($this->feed as $message)
            <div class="border-b border-zinc-100 pb-3 last:border-b-0 dark:border-zinc-800" wire:key="msg-{{ $message->id }}">
                <div class="flex items-center gap-2 text-sm">
                    @if ($message->from_id === auth()->id())
                        <flux:badge size="sm" color="blue">{{ __('Aan') }} {{ $message->recipient?->name }}</flux:badge>
                    @else
                        <flux:badge size="sm" color="green">{{ __('Van') }} {{ $message->sender?->name }}</flux:badge>
                    @endif
                    @if ($message->ticket)
                        <flux:link :href="route('tickets.show', $message->ticket)" wire:navigate>{{ $message->ticket->number }}</flux:link>
                    @endif
                    <flux:text size="sm" variant="subtle">{{ $message->sent_at->format('d-m-Y H:i') }}</flux:text>
                </div>
                <flux:text class="mt-1">{{ $message->body }}</flux:text>
            </div>
        @empty
            <flux:text size="sm" variant="subtle">{{ __('Nog geen berichten.') }}</flux:text>
        @endforelse
    </flux:card>
</section>
