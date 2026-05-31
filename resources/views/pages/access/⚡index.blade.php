<?php

use App\Enums\Ability;
use App\Enums\Role;
use App\Livewire\Concerns\InteractsWithStudentWorld;
use App\Models\User;
use App\Models\VisitorLog;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new #[\Livewire\Attributes\Title('Toegangsregister')] class extends Component
{
    use InteractsWithStudentWorld;

    public string $visitor_name = '';

    public string $company = '';

    public string $reason = '';

    public string $badge_number = '';

    public ?int $escort_id = null;

    /**
     * @return Collection<int, VisitorLog>
     */
    #[Computed]
    public function visitors(): Collection
    {
        return VisitorLog::with('escort')->latest()->get();
    }

    /**
     * Staff members who can act as an escort.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function escorts(): Collection
    {
        return User::whereIn('role', [Role::Technicus->value, Role::Leidinggevende->value])
            ->orderBy('name')
            ->get();
    }

    /**
     * Register a visitor (records the check-in moment).
     */
    public function register(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        $validated = $this->validate([
            'visitor_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:255'],
            'badge_number' => ['nullable', 'string', 'max:255'],
            'escort_id' => ['nullable', Rule::exists('users', 'id')],
        ]);

        $visitor = VisitorLog::create([...$validated, 'created_by' => Auth::id()]);
        $visitor->forceFill(['checked_in_at' => now()])->save();

        $this->reset(['visitor_name', 'company', 'reason', 'badge_number', 'escort_id']);

        Flux::toast(variant: 'success', text: __('Bezoeker aangemeld.'));
    }

    /**
     * Check a visitor out (records the check-out moment).
     */
    public function checkOut(int $visitorId): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        $visitor = VisitorLog::findOrFail($visitorId);

        if ($visitor->isOpen()) {
            $visitor->forceFill(['checked_out_at' => now()])->save();
            Flux::toast(variant: 'success', text: __('Bezoeker afgemeld.'));
        }
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Toegangsregister') }}</flux:heading>
        <flux:subheading>{{ __('Meld bezoekers aan en af; een bezoek is pas compleet bij aan- én afmelding.') }}</flux:subheading>
    </div>

    @can('execute-tasks')
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Bezoeker aanmelden') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="visitor_name" :label="__('Naam bezoeker')" required />
                <flux:input wire:model="company" :label="__('Bedrijf')" />
                <flux:input wire:model="reason" :label="__('Reden van bezoek')" required />
                <flux:input wire:model="badge_number" :label="__('Badgenummer')" />
                <flux:select wire:model="escort_id" :label="__('Begeleider')">
                    <flux:select.option :value="null">{{ __('— geen —') }}</flux:select.option>
                    @foreach ($this->escorts as $escort)
                        <flux:select.option :value="$escort->id">{{ $escort->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button variant="primary" icon="user-plus" wire:click="register" data-test="register-visitor-button">
                {{ __('Aanmelden') }}
            </flux:button>
        </flux:card>
    @endcan

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Bezoeker') }}</flux:table.column>
            <flux:table.column>{{ __('Reden') }}</flux:table.column>
            <flux:table.column>{{ __('Aangemeld') }}</flux:table.column>
            <flux:table.column>{{ __('Afgemeld') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->visitors as $visitor)
                <flux:table.row wire:key="visitor-{{ $visitor->id }}" @class(['bg-amber-50 dark:bg-amber-950/40' => $visitor->isOpen()])>
                    <flux:table.cell>
                        <span class="font-medium">{{ $visitor->visitor_name }}</span>
                        @if ($visitor->company)
                            <flux:text size="sm" variant="subtle">{{ $visitor->company }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $visitor->reason }}</flux:table.cell>
                    <flux:table.cell>{{ $visitor->checked_in_at?->format('d-m-Y H:i') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $visitor->checked_out_at?->format('d-m-Y H:i') ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($visitor->isOpen())
                            <flux:badge size="sm" color="amber" icon="exclamation-triangle" data-test="open-visit">{{ __('Open — nog binnen') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="green">{{ __('Compleet') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @can('execute-tasks')
                            @if ($visitor->isOpen())
                                <flux:button size="sm" variant="primary" wire:click="checkOut({{ $visitor->id }})" data-test="checkout-{{ $visitor->id }}">
                                    {{ __('Afmelden') }}
                                </flux:button>
                            @endif
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ __('Nog geen bezoekers geregistreerd.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
