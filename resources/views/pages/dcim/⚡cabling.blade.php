<?php

use App\Models\Cable;
use App\Models\Rack;
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
     * The racks with their devices, for the interactive patch grid.
     *
     * @return Collection<int, Rack>
     */
    #[Computed]
    public function racks(): Collection
    {
        return Rack::with('devices')->orderBy('name')->get();
    }

    /**
     * Which device port is occupied by which cable, keyed "deviceId-port".
     *
     * @return array<string, array{cableId: int, label: string, dot: string}>
     */
    #[Computed]
    public function portMap(): array
    {
        $map = [];

        foreach ($this->cables as $cable) {
            foreach ([[$cable->from_device_id, $cable->from_port], [$cable->to_device_id, $cable->to_port]] as [$deviceId, $port]) {
                $map["{$deviceId}-{$port}"] = [
                    'cableId' => $cable->id,
                    'label' => $cable->label,
                    'dot' => $cable->medium->dotClass(),
                ];
            }
        }

        return $map;
    }

    /**
     * Endpoint element ids + colour per cable, for the SVG line overlay.
     *
     * @return array<int, array{from: string, to: string, color: string}>
     */
    #[Computed]
    public function lines(): array
    {
        return $this->cables->map(fn (Cable $cable): array => [
            'from' => "port-{$cable->from_device_id}-{$cable->from_port}",
            'to' => "port-{$cable->to_device_id}-{$cable->to_port}",
            'color' => $cable->medium->strokeClass(),
        ])->all();
    }

    /**
     * Re-render after the cable form saves or deletes a cable.
     */
    #[On('cable-saved')]
    public function refresh(): void
    {
        unset($this->cables, $this->racks, $this->portMap, $this->lines);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('DCIM — Bekabeling') }}</flux:heading>
            <flux:subheading>{{ __('Verbind poorten van apparaten en patchpanelen, en nummer elke kabel.') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            @if ($this->cables->isNotEmpty())
                <flux:button icon="arrow-down-tray" :href="route('dcim.cabling.pdf')" target="_blank">
                    {{ __('Kabelstaat (PDF)') }}
                </flux:button>
            @endif
            @can('execute-tasks')
                <flux:button variant="primary" icon="plus" wire:click="$dispatch('add-cable')" data-test="add-cable-button">
                    {{ __('Kabel toevoegen') }}
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Interactief patchen: klik een vrije poort en daarna een tweede om te
         verbinden. Bezette poorten zijn gekleurd; klik erop om de kabel te
         bewerken. De SVG-laag tekent de verbindingen tussen de poorten. --}}
    <flux:heading size="lg" class="mb-1">{{ __('Patchen') }}</flux:heading>
    <flux:subheading class="mb-3">{{ __('Klik een vrije poort en daarna een tweede poort om ze te verbinden.') }}</flux:subheading>

    <div
        class="relative mb-8"
        x-data="{
            lines: @js($this->lines),
            init() {
                this.$nextTick(() => this.draw());
                window.addEventListener('resize', () => this.draw());
                document.addEventListener('livewire:navigated', () => this.$nextTick(() => this.draw()));
                if (window.Livewire) {
                    try { Livewire.hook('commit', ({ succeed }) => succeed(() => requestAnimationFrame(() => this.draw()))); } catch (e) {}
                }
            },
            draw() {
                const svg = this.$refs.lines;
                if (!svg) return;
                const box = svg.getBoundingClientRect();
                while (svg.firstChild) svg.removeChild(svg.firstChild);
                this.lines.forEach((c) => {
                    const a = document.getElementById(c.from), b = document.getElementById(c.to);
                    if (!a || !b) return;
                    const ra = a.getBoundingClientRect(), rb = b.getBoundingClientRect();
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', ra.left + ra.width / 2 - box.left);
                    line.setAttribute('y1', ra.top + ra.height / 2 - box.top);
                    line.setAttribute('x2', rb.left + rb.width / 2 - box.left);
                    line.setAttribute('y2', rb.top + rb.height / 2 - box.top);
                    line.setAttribute('stroke-width', '2');
                    line.setAttribute('stroke-linecap', 'round');
                    line.setAttribute('class', c.color);
                    svg.appendChild(line);
                });
            },
            sel: null,
            pick(key, deviceId, port) {
                if (this.sel === null) { this.sel = key; return; }
                if (this.sel === key) { this.sel = null; return; }
                const [fd, fp] = this.sel.split('-');
                this.$wire.dispatch('patch-ports', { fromDeviceId: +fd, fromPort: +fp, toDeviceId: deviceId, toPort: port });
                this.sel = null;
            },
        }"
    >
        <svg x-ref="lines" class="pointer-events-none absolute inset-0 h-full w-full" style="overflow: visible; z-index: 5;"></svg>

        <div class="grid gap-6 md:grid-cols-2">
            @forelse ($this->racks as $rack)
                <flux:card wire:key="patch-rack-{{ $rack->id }}" class="space-y-3">
                    <flux:heading size="lg">{{ $rack->name }}</flux:heading>

                    @forelse ($rack->devices as $device)
                        <div wire:key="patch-device-{{ $device->id }}" class="rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                            <flux:text size="sm" class="mb-1 font-medium">{{ $device->name }} <span class="text-zinc-400">· {{ $device->type->label() }}</span></flux:text>
                            <div class="flex flex-wrap gap-1">
                                @for ($port = 1; $port <= $device->port_count; $port++)
                                    @php($key = $device->id.'-'.$port)
                                    @php($occ = $this->portMap[$key] ?? null)
                                    @php($dotClass = $occ['dot'] ?? '')
                                    <button
                                        type="button"
                                        id="port-{{ $key }}"
                                        @can('execute-tasks')
                                            @if ($occ)
                                                wire:click="$dispatch('edit-cable', { cableId: {{ $occ['cableId'] }} })"
                                            @else
                                                x-on:click="pick('{{ $key }}', {{ $device->id }}, {{ $port }})"
                                            @endif
                                        @endcan
                                        :class="sel === '{{ $key }}' ? 'ring-2 ring-offset-1 ring-blue-500' : ''"
                                        @class([
                                            'relative flex h-6 w-6 items-center justify-center rounded border text-[10px] font-mono',
                                            $dotClass.' text-white' => $occ,
                                            'border-zinc-300 bg-white text-zinc-500 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800' => ! $occ,
                                        ])
                                        title="{{ $device->name }} · poort {{ $port }}{{ $occ ? ' · '.$occ['label'] : '' }}"
                                    >{{ $port }}</button>
                                @endfor
                            </div>
                        </div>
                    @empty
                        <flux:text size="sm" variant="subtle">{{ __('Nog geen apparaten in dit rack.') }}</flux:text>
                    @endforelse
                </flux:card>
            @empty
                <flux:callout icon="bolt">
                    <flux:callout.heading>{{ __('Nog geen racks of apparaten') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Maak eerst een rack met apparaten aan in DCIM — Racks.') }}</flux:callout.text>
                </flux:callout>
            @endforelse
        </div>
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
