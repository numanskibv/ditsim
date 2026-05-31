<?php

use App\Enums\Ability;
use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Scenario;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Scenariopaneel')] class extends Component
{
    public ?int $manualDeviceId = null;

    public string $manualStatus = '';

    public string $name = '';

    public string $description = '';

    /**
     * @var array<int, array{delay: int|string, device_id: int|string|null, status: string}>
     */
    public array $actions = [];

    public function mount(): void
    {
        $this->authorize(Ability::ManageScenarios->value);
        $this->addAction();
    }

    /**
     * @return Collection<int, Device>
     */
    #[Computed]
    public function devices(): Collection
    {
        return Device::with('rack')->orderBy('name')->get();
    }

    /**
     * @return array<int, DeviceStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return DeviceStatus::cases();
    }

    /**
     * @return Collection<int, Scenario>
     */
    #[Computed]
    public function scenarios(): Collection
    {
        return Scenario::latest()->get();
    }

    /**
     * Immediately force a single device to a status (the "one click storing"
     * trigger). The Device hook broadcasts it to the dashboard.
     */
    public function setManualStatus(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        $this->validate([
            'manualDeviceId' => ['required', Rule::exists('devices', 'id')],
            'manualStatus' => ['required', Rule::enum(DeviceStatus::class)],
        ]);

        Device::findOrFail($this->manualDeviceId)
            ->update(['status' => DeviceStatus::from($this->manualStatus)]);

        Flux::toast(variant: 'success', text: __('Status toegepast en uitgezonden.'));
    }

    public function addAction(): void
    {
        $this->actions[] = ['delay' => 0, 'device_id' => null, 'status' => DeviceStatus::Storing->value];
    }

    public function removeAction(int $index): void
    {
        unset($this->actions[$index]);
        $this->actions = array_values($this->actions);
    }

    public function saveScenario(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'actions' => ['array', 'min:1'],
            'actions.*.delay' => ['required', 'integer', 'min:0'],
            'actions.*.device_id' => ['required', Rule::exists('devices', 'id')],
            'actions.*.status' => ['required', Rule::enum(DeviceStatus::class)],
        ]);

        Scenario::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'actions' => $validated['actions'],
            'created_by' => Auth::id(),
        ]);

        $this->reset(['name', 'description', 'actions']);
        $this->addAction();

        Flux::toast(variant: 'success', text: __('Scenario opgeslagen.'));
    }

    public function startScenario(int $scenarioId): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        Scenario::findOrFail($scenarioId)->start();

        Flux::toast(variant: 'success', text: __('Scenario gestart — acties zijn ingepland.'));
    }
}; ?>

<section class="w-full max-w-4xl space-y-8">
    <div>
        <flux:heading size="xl">{{ __('Scenariopaneel (docent)') }}</flux:heading>
        <flux:subheading>{{ __('Trigger realistische signalen voor cursisten — geen A4\'tjes meer.') }}</flux:subheading>
    </div>

    {{-- Manual instant trigger --}}
    <flux:card class="space-y-4">
        <flux:heading size="lg">{{ __('Directe status') }}</flux:heading>
        <div class="flex flex-wrap items-end gap-3">
            <flux:select wire:model="manualDeviceId" :label="__('Device')" class="max-w-xs">
                <flux:select.option :value="null">{{ __('— kies device —') }}</flux:select.option>
                @foreach ($this->devices as $device)
                    <flux:select.option :value="$device->id">{{ $device->name }} ({{ $device->rack?->name }})</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="manualStatus" :label="__('Status')" class="max-w-xs">
                <flux:select.option value="">{{ __('— kies status —') }}</flux:select.option>
                @foreach ($this->statuses as $status)
                    <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:button variant="primary" icon="bolt" wire:click="setManualStatus" data-test="apply-status-button">
                {{ __('Direct toepassen') }}
            </flux:button>
        </div>
        @error('manualDeviceId') <flux:text color="red">{{ $message }}</flux:text> @enderror
        @error('manualStatus') <flux:text color="red">{{ $message }}</flux:text> @enderror
    </flux:card>

    {{-- Scenario builder --}}
    <flux:card class="space-y-4">
        <flux:heading size="lg">{{ __('Scenario samenstellen') }}</flux:heading>

        <flux:input wire:model="name" :label="__('Naam')" required />
        <flux:textarea wire:model="description" :label="__('Beschrijving')" rows="2" />

        <div class="space-y-3">
            <flux:text variant="strong">{{ __('Acties (met vertraging in seconden)') }}</flux:text>
            @foreach ($actions as $index => $action)
                <div class="flex flex-wrap items-end gap-3" wire:key="action-{{ $index }}">
                    <flux:input type="number" min="0" wire:model="actions.{{ $index }}.delay" :label="__('Na (s)')" class="max-w-28" />
                    <flux:select wire:model="actions.{{ $index }}.device_id" :label="__('Device')" class="max-w-xs">
                        <flux:select.option :value="null">{{ __('— kies —') }}</flux:select.option>
                        @foreach ($this->devices as $device)
                            <flux:select.option :value="$device->id">{{ $device->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="actions.{{ $index }}.status" :label="__('Status')" class="max-w-xs">
                        @foreach ($this->statuses as $status)
                            <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button variant="subtle" icon="trash" wire:click="removeAction({{ $index }})" />
                </div>
            @endforeach
            @error('actions') <flux:text color="red">{{ $message }}</flux:text> @enderror

            <flux:button size="sm" variant="ghost" icon="plus" wire:click="addAction">{{ __('Actie toevoegen') }}</flux:button>
        </div>

        <flux:button variant="primary" wire:click="saveScenario" data-test="save-scenario-button">{{ __('Scenario opslaan') }}</flux:button>
    </flux:card>

    {{-- Saved scenarios --}}
    <flux:card class="space-y-3">
        <flux:heading size="lg">{{ __('Opgeslagen scenario\'s') }}</flux:heading>
        @forelse ($this->scenarios as $scenario)
            <div class="flex items-center justify-between border-b border-zinc-100 pb-2 last:border-b-0 dark:border-zinc-800" wire:key="scenario-{{ $scenario->id }}">
                <div>
                    <flux:text variant="strong">{{ $scenario->name }}</flux:text>
                    <flux:text size="sm" variant="subtle">{{ count($scenario->actions) }} {{ __('actie(s)') }}</flux:text>
                </div>
                <flux:button size="sm" variant="primary" icon="play" wire:click="startScenario({{ $scenario->id }})" data-test="start-scenario-{{ $scenario->id }}">
                    {{ __('Start') }}
                </flux:button>
            </div>
        @empty
            <flux:text size="sm" variant="subtle">{{ __('Nog geen scenario\'s opgeslagen.') }}</flux:text>
        @endforelse
    </flux:card>
</section>
