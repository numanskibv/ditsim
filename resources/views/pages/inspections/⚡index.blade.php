<?php

use App\Enums\Ability;
use App\Enums\InspectionStatus;
use App\Livewire\Concerns\InteractsWithStudentWorld;
use App\Models\Device;
use App\Models\InspectionReport;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Inspectieronde')] class extends Component
{
    use InteractsWithStudentWorld;

    public string $date = '';

    /**
     * @var array<string, array{status: string, observation: string, device_id: int|null}>
     */
    public array $items = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();

        foreach (array_keys(InspectionReport::CONTROL_POINTS) as $key) {
            $this->items[$key] = [
                'status' => InspectionStatus::Ok->value,
                'observation' => '',
                'device_id' => null,
            ];
        }
    }

    /**
     * @return Collection<int, Device>
     */
    #[Computed]
    public function devices(): Collection
    {
        return Device::orderBy('name')->get();
    }

    /**
     * @return array<int, InspectionStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return InspectionStatus::cases();
    }

    /**
     * @return Collection<int, InspectionReport>
     */
    #[Computed]
    public function reports(): Collection
    {
        return InspectionReport::with('inspector')->latest()->get();
    }

    /**
     * Save the inspection round as a report. Findings (Afwijking/Actie) that
     * are linked to a device push that device into the matching status, so a
     * docent can plant "hidden faults" via an inspection.
     */
    public function save(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);
        $this->requireActiveStudent();

        $this->validate([
            'date' => ['required', 'date'],
            'items.*.status' => ['required', Rule::enum(InspectionStatus::class)],
            'items.*.observation' => ['nullable', 'string'],
            'items.*.device_id' => ['nullable', Rule::exists('devices', 'id')],
        ]);

        $items = [];
        foreach (InspectionReport::CONTROL_POINTS as $key => $label) {
            $items[] = [
                'key' => $key,
                'label' => $label,
                'status' => $this->items[$key]['status'],
                'observation' => $this->items[$key]['observation'] ?? '',
                'device_id' => $this->items[$key]['device_id'] ?? null,
            ];
        }

        InspectionReport::create([
            'inspector_id' => Auth::id(),
            'date' => $this->date,
            'items' => $items,
        ]);

        $this->applyDeviceFindings($items);

        $this->mount();

        Flux::toast(variant: 'success', text: __('Inspectierapport opgeslagen.'));
    }

    /**
     * Push device statuses for findings linked to a device.
     *
     * @param  array<int, array{status: string, device_id: int|null}>  $items
     */
    protected function applyDeviceFindings(array $items): void
    {
        foreach ($items as $item) {
            $deviceStatus = InspectionStatus::from($item['status'])->deviceStatus();

            if ($deviceStatus !== null && $item['device_id'] !== null) {
                Device::find($item['device_id'])?->update(['status' => $deviceStatus]);
            }
        }
    }
}; ?>

<section class="w-full max-w-4xl space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Inspectieronde') }}</flux:heading>
        <flux:subheading>{{ __('Loop de vaste controlepunten langs en leg je waarnemingen vast.') }}</flux:subheading>
    </div>

    @can('execute-tasks')
        <flux:card class="space-y-5">
            <flux:input type="date" wire:model="date" :label="__('Datum')" class="max-w-xs" />

            @foreach (\App\Models\InspectionReport::CONTROL_POINTS as $key => $label)
                <div class="space-y-2 border-b border-zinc-100 pb-4 last:border-b-0 dark:border-zinc-800" wire:key="cp-{{ $key }}">
                    <flux:heading size="sm">{{ $label }}</flux:heading>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <flux:select wire:model="items.{{ $key }}.status" :label="__('Beoordeling')">
                            @foreach ($this->statuses as $status)
                                <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="items.{{ $key }}.device_id" :label="__('Koppel device (optioneel)')">
                            <flux:select.option :value="null">{{ __('— geen —') }}</flux:select.option>
                            @foreach ($this->devices as $device)
                                <flux:select.option :value="$device->id">{{ $device->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="items.{{ $key }}.observation" :label="__('Waarneming')" />
                    </div>
                </div>
            @endforeach

            <flux:button variant="primary" icon="clipboard-document-check" wire:click="save" data-test="save-inspection-button">
                {{ __('Rapport opslaan') }}
            </flux:button>
        </flux:card>
    @endcan

    <flux:card class="space-y-3">
        <flux:heading size="lg">{{ __('Eerdere rapporten') }}</flux:heading>
        @forelse ($this->reports as $report)
            <div class="border-b border-zinc-100 pb-3 last:border-b-0 dark:border-zinc-800" wire:key="report-{{ $report->id }}">
                <flux:text variant="strong">{{ $report->date->format('d-m-Y') }} — {{ $report->inspector?->name ?? __('onbekend') }}</flux:text>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($report->items as $item)
                        <flux:badge size="sm" :color="\App\Enums\InspectionStatus::from($item['status'])->color()">
                            {{ $item['label'] }}: {{ \App\Enums\InspectionStatus::from($item['status'])->label() }}
                        </flux:badge>
                    @endforeach
                </div>
            </div>
        @empty
            <flux:text size="sm" variant="subtle">{{ __('Nog geen rapporten.') }}</flux:text>
        @endforelse
    </flux:card>
</section>
