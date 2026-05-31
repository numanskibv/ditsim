<?php

use App\Models\Rack;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('DCIM — Racks')] class extends Component
{
    /**
     * All racks with their devices and audit information, ready to render.
     *
     * @return Collection<int, Rack>
     */
    #[Computed]
    public function racks(): Collection
    {
        return Rack::with(['devices.lastChangedBy', 'devices.owner'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Re-render after the device form saves so the grid and the
     * "laatste wijziging" indicator reflect the change immediately.
     */
    #[On('device-saved')]
    public function refresh(): void
    {
        unset($this->racks);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('DCIM — Racks') }}</flux:heading>
            <flux:subheading>{{ __('Visueel overzicht van racks en devices per U-positie.') }}</flux:subheading>
        </div>

        @can('execute-tasks')
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('add-device')">
                {{ __('Device toevoegen') }}
            </flux:button>
        @endcan
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->racks as $rack)
            @php($lastChanged = $rack->lastChangedDevice())
            <flux:card wire:key="rack-{{ $rack->id }}" class="space-y-3">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">{{ $rack->name }}</flux:heading>
                        <flux:text size="sm" variant="subtle">{{ $rack->location }} · {{ $rack->height_u }}U</flux:text>
                    </div>
                    @can('execute-tasks')
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="plus"
                            wire:click="$dispatch('add-device', { rackId: {{ $rack->id }} })"
                        >
                            {{ __('Device') }}
                        </flux:button>
                    @endcan
                </div>

                <flux:text size="sm" variant="subtle" data-test="last-changed-{{ $rack->id }}">
                    @if ($lastChanged && $lastChanged->last_changed_at)
                        {{ __('Laatste wijziging') }}:
                        <span class="font-medium">{{ $lastChanged->lastChangedBy?->name ?? __('onbekend') }}</span>
                        · {{ $lastChanged->last_changed_at->format('d-m-Y H:i') }}
                    @else
                        {{ __('Nog geen wijzigingen') }}
                    @endif
                </flux:text>

                <div class="flex flex-col overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @for ($u = $rack->height_u; $u >= 1; $u--)
                        @php($device = $rack->deviceAt($u))
                        <div class="flex h-7 items-stretch border-b border-zinc-100 last:border-b-0 dark:border-zinc-800" wire:key="rack-{{ $rack->id }}-u-{{ $u }}">
                            <div class="flex w-9 shrink-0 items-center justify-center bg-zinc-50 text-[10px] font-mono text-zinc-400 dark:bg-zinc-900">
                                {{ $u }}
                            </div>

                            @if ($device)
                                @php($isTop = $u === $device->u_end)
                                @can('execute-tasks')
                                    <button
                                        type="button"
                                        wire:click="$dispatch('edit-device', { deviceId: {{ $device->id }} })"
                                        class="flex flex-1 items-center gap-2 border-l px-2 text-left text-xs {{ $device->status->cellClasses() }}"
                                        title="{{ $device->name }} · {{ $device->status->label() }} · U{{ $device->u_start }}–{{ $device->u_end }}"
                                    >
                                        @if ($isTop)
                                            <span class="font-semibold">{{ $device->name }}</span>
                                            <span class="opacity-80">({{ $device->status->label() }})</span>
                                        @endif
                                    </button>
                                @else
                                    <div
                                        class="flex flex-1 items-center gap-2 border-l px-2 text-xs {{ $device->status->cellClasses() }}"
                                        title="{{ $device->name }} · {{ $device->status->label() }} · U{{ $device->u_start }}–{{ $device->u_end }}"
                                    >
                                        @if ($isTop)
                                            <span class="font-semibold">{{ $device->name }}</span>
                                            <span class="opacity-80">({{ $device->status->label() }})</span>
                                        @endif
                                    </div>
                                @endcan
                            @else
                                <div class="flex-1 border-l border-zinc-100 bg-white dark:border-zinc-800 dark:bg-zinc-800/40"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </flux:card>
        @empty
            <flux:callout icon="server-stack">
                <flux:callout.heading>{{ __('Nog geen racks') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Seed de demodata met') }} <code>php artisan migrate:fresh --seed</code>.</flux:callout.text>
            </flux:callout>
        @endforelse
    </div>

    <livewire:dcim.device-form />
</section>
