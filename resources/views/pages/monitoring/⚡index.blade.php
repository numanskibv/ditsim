<?php

use App\Enums\Ability;
use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Models\Device;
use App\Models\DeviceAlert;
use App\Support\CurrentStudent;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Monitoring')] class extends Component
{
    /**
     * All devices with their current live status and metrics.
     *
     * @return Collection<int, Device>
     */
    #[Computed]
    public function devices(): Collection
    {
        return Device::with('rack')->orderBy('name')->get();
    }

    /**
     * The most recent NOC alerts for the alarm panel.
     *
     * @return Collection<int, DeviceAlert>
     */
    #[Computed]
    public function alerts(): Collection
    {
        return DeviceAlert::with('device')->latest()->limit(15)->get();
    }

    /**
     * Count of devices per status for the summary header.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return $this->devices
            ->groupBy(fn (Device $device): string => $device->status->value)
            ->map(fn (Collection $group): int => $group->count())
            ->all();
    }

    /**
     * Subscribe to the monitoring channel of the world currently in scope, so
     * a status change refreshes this dashboard without a page reload. With no
     * single world in scope (a shared role viewing "all") we rely on polling.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        $world = app(CurrentStudent::class)->id();

        if ($world === null) {
            return [];
        }

        return ['echo:'.DeviceStatusChanged::channelFor($world).',.status.changed' => 'onStatusBroadcast'];
    }

    public function onStatusBroadcast(): void
    {
        unset($this->devices, $this->alerts, $this->statusCounts);
    }

    /**
     * Manually run a simulation tick from the UI (handy for the demo). It only
     * ticks the world currently in scope, never another student's.
     */
    public function runTick(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $world = app(CurrentStudent::class)->id();

        Artisan::call('simulate:tick', $world !== null ? ['--student' => $world] : []);

        unset($this->devices, $this->alerts, $this->statusCounts);

        Flux::toast(variant: 'success', text: __('Simulatie-tick uitgevoerd.'));
    }
}; ?>

<section class="w-full" wire:poll.10s>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Monitoring (NOC)') }}</flux:heading>
            <flux:subheading>{{ __('Realtime status en metrics van alle devices.') }}</flux:subheading>
        </div>

        @can('execute-tasks')
            <flux:button variant="primary" icon="bolt" wire:click="runTick" wire:loading.attr="disabled">
                {{ __('Simuleer tick') }}
            </flux:button>
        @endcan
    </div>

    {{-- Status summary --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (DeviceStatus::cases() as $status)
            <flux:badge :color="$status->color()">
                {{ $status->label() }}: {{ $this->statusCounts[$status->value] ?? 0 }}
            </flux:badge>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Device grid --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
            @foreach ($this->devices as $device)
                <flux:card wire:key="mon-device-{{ $device->id }}" class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:heading size="sm">{{ $device->name }}</flux:heading>
                            <flux:text size="sm" variant="subtle">{{ $device->rack?->name }} · {{ $device->type->label() }}</flux:text>
                        </div>
                        <flux:badge size="sm" :color="$device->status->color()">{{ $device->status->label() }}</flux:badge>
                    </div>

                    @php($cpuColor = $device->cpu >= \App\Models\Device::CPU_FAULT ? 'bg-red-500' : ($device->cpu >= \App\Models\Device::CPU_WARN ? 'bg-orange-500' : 'bg-emerald-500'))
                    @php($tempColor = $device->temp >= \App\Models\Device::TEMP_FAULT ? 'bg-red-500' : ($device->temp >= \App\Models\Device::TEMP_WARN ? 'bg-orange-500' : 'bg-emerald-500'))

                    <div>
                        <div class="mb-1 flex justify-between text-xs text-zinc-500">
                            <span>{{ __('CPU') }}</span><span>{{ $device->cpu }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full {{ $cpuColor }}" style="width: {{ min($device->cpu, 100) }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex justify-between text-xs text-zinc-500">
                            <span>{{ __('Temperatuur') }}</span><span>{{ $device->temp }}°C</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full {{ $tempColor }}" style="width: {{ min((int) round($device->temp / 120 * 100), 100) }}%"></div>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        {{-- Alarm panel --}}
        <flux:card class="space-y-3">
            <flux:heading size="sm">{{ __('Alarmen / meldingen') }}</flux:heading>

            <div class="space-y-3" data-test="alarm-panel">
                @forelse ($this->alerts as $alert)
                    <div class="flex items-start gap-2 border-b border-zinc-100 pb-2 last:border-b-0 dark:border-zinc-800" wire:key="alert-{{ $alert->id }}">
                        <flux:badge size="sm" :color="$alert->to_status->color()">{{ $alert->to_status->label() }}</flux:badge>
                        <div class="min-w-0">
                            <flux:text size="sm" class="truncate">
                                <span class="font-medium">{{ $alert->device?->name }}</span> — {{ $alert->message }}
                            </flux:text>
                            <flux:text size="sm" variant="subtle">{{ $alert->created_at->format('d-m-Y H:i:s') }}</flux:text>
                        </div>
                    </div>
                @empty
                    <flux:text size="sm" variant="subtle">{{ __('Nog geen meldingen.') }}</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>
</section>
