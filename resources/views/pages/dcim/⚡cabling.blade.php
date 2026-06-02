<?php

use App\Models\Cable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('DCIM — Bekabeling')] class extends Component
{
    /**
     * All cables in the current world with their endpoints, ready to render
     * as the cable schedule and the schema.
     *
     * @return Collection<int, Cable>
     */
    #[Computed]
    public function cables(): Collection
    {
        return Cable::with(['fromDevice.rack', 'toDevice.rack', 'lastChangedBy'])
            ->orderBy('label')
            ->get();
    }

    /**
     * Re-render after the cable form saves or deletes a cable.
     */
    #[On('cable-saved')]
    public function refresh(): void
    {
        unset($this->cables);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('DCIM — Bekabeling') }}</flux:heading>
            <flux:subheading>{{ __('Verbind poorten van apparaten en patchpanelen, en nummer elke kabel.') }}</flux:subheading>
        </div>

        @can('execute-tasks')
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('add-cable')" data-test="add-cable-button">
                {{ __('Kabel toevoegen') }}
            </flux:button>
        @endcan
    </div>

    {{-- Visueel schema: elke kabel als gekleurde verbinding tussen twee poorten. --}}
    <flux:heading size="lg" class="mb-3">{{ __('Schema') }}</flux:heading>
    <div class="mb-8 space-y-2">
        @forelse ($this->cables as $cable)
            <flux:card wire:key="schema-{{ $cable->id }}" class="flex items-center gap-3 py-3">
                <div class="w-16 shrink-0 font-mono text-sm font-semibold">{{ $cable->label }}</div>

                <div class="rounded-md border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <span class="font-medium">{{ $cable->fromDevice?->name ?? __('?') }}</span>
                    <span class="text-zinc-400">· poort {{ $cable->from_port }}</span>
                </div>

                <div class="flex flex-1 items-center gap-2">
                    <svg class="h-4 flex-1" preserveAspectRatio="none" viewBox="0 0 100 10">
                        <line x1="0" y1="5" x2="100" y2="5" class="{{ $cable->medium->strokeClass() }}" stroke-width="2" />
                    </svg>
                    <flux:badge size="sm">{{ $cable->medium->label() }}</flux:badge>
                </div>

                <div class="rounded-md border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <span class="font-medium">{{ $cable->toDevice?->name ?? __('?') }}</span>
                    <span class="text-zinc-400">· poort {{ $cable->to_port }}</span>
                </div>

                @can('execute-tasks')
                    <div class="flex gap-1">
                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="$dispatch('edit-cable', { cableId: {{ $cable->id }} })" />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="$dispatch('delete-cable', { cableId: {{ $cable->id }} })"
                            wire:confirm="{{ __('Deze kabel verwijderen?') }}"
                            data-test="delete-cable-{{ $cable->id }}"
                        />
                    </div>
                @endcan
            </flux:card>
        @empty
            <flux:callout icon="bolt">
                <flux:callout.heading>{{ __('Nog geen kabels') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Voeg een kabel toe om poorten met elkaar te verbinden.') }}</flux:callout.text>
            </flux:callout>
        @endforelse
    </div>

    {{-- Kabelstaat: de tabel die als bewijs dient. --}}
    @if ($this->cables->isNotEmpty())
        <flux:heading size="lg" class="mb-3">{{ __('Kabelstaat') }}</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Nr') }}</flux:table.column>
                <flux:table.column>{{ __('Van') }}</flux:table.column>
                <flux:table.column>{{ __('Naar') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Kleur') }}</flux:table.column>
                <flux:table.column>{{ __('Laatste wijziging') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->cables as $cable)
                    <flux:table.row wire:key="staat-{{ $cable->id }}">
                        <flux:table.cell variant="strong">{{ $cable->label }}</flux:table.cell>
                        <flux:table.cell>{{ $cable->fromDevice?->name ?? __('?') }} : {{ $cable->from_port }}</flux:table.cell>
                        <flux:table.cell>{{ $cable->toDevice?->name ?? __('?') }} : {{ $cable->to_port }}</flux:table.cell>
                        <flux:table.cell>{{ $cable->medium->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $cable->color ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($cable->last_changed_at)
                                {{ $cable->lastChangedBy?->name ?? __('onbekend') }} · {{ $cable->last_changed_at->format('d-m-Y H:i') }}
                            @else
                                —
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <livewire:dcim.cable-form />
</section>
