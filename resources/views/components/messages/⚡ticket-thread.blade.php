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
use Livewire\Component;

new class extends Component
{
    use InteractsWithStudentWorld;

    public Ticket $ticket;

    public ?int $to_id = null;

    public string $body = '';

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $this->to_id = $ticket->assigned_to ?? $ticket->created_by;
    }

    /**
     * The message timeline for this ticket.
     *
     * @return Collection<int, Message>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->ticket->messages()->with(['sender', 'recipient'])->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function recipients(): Collection
    {
        return User::whereKeyNot(Auth::id())->orderBy('name')->get();
    }

    public function send(): void
    {
        $this->requireActiveStudent();

        $validated = $this->validate([
            'to_id' => ['required', Rule::exists('users', 'id'), Rule::notIn([Auth::id()])],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Message::create([...$validated, 'ticket_id' => $this->ticket->id]);

        $this->reset('body');
        unset($this->timeline);

        Flux::toast(variant: 'success', text: __('Bericht toegevoegd aan ticket.'));
    }
}; ?>

<div class="space-y-4">
    <flux:heading size="sm">{{ __('Communicatie') }}</flux:heading>

    <div class="space-y-3" data-test="ticket-timeline">
        @forelse ($this->timeline as $message)
            <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800" wire:key="tmsg-{{ $message->id }}">
                <div class="flex items-center gap-2 text-sm">
                    <span class="font-medium">{{ $message->sender?->name }}</span>
                    <span class="text-zinc-400">→ {{ $message->recipient?->name }}</span>
                    <flux:text size="sm" variant="subtle">{{ $message->sent_at->format('d-m-Y H:i') }}</flux:text>
                </div>
                <flux:text class="mt-1">{{ $message->body }}</flux:text>
            </div>
        @empty
            <flux:text size="sm" variant="subtle">{{ __('Nog geen berichten voor dit ticket.') }}</flux:text>
        @endforelse
    </div>

    <div class="flex flex-col gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
        <flux:select wire:model="to_id" :label="__('Aan')" class="max-w-xs">
            <flux:select.option :value="null">{{ __('— kies ontvanger —') }}</flux:select.option>
            @foreach ($this->recipients as $recipient)
                <flux:select.option :value="$recipient->id">{{ $recipient->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="body" :label="__('Bericht')" rows="2" />
        @error('to_id') <flux:text color="red">{{ $message }}</flux:text> @enderror
        <div>
            <flux:button size="sm" variant="primary" icon="paper-airplane" wire:click="send" data-test="send-ticket-message-button">
                {{ __('Versturen') }}
            </flux:button>
        </div>
    </div>
</div>
