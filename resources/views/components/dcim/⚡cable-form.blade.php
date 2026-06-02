<?php

use App\Enums\Ability;
use App\Enums\CableMedium;
use App\Models\Cable;
use App\Models\Device;
use App\Support\CurrentStudent;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $showModal = false;

    public ?int $cableId = null;

    public string $label = '';

    public string $medium = '';

    public ?string $color = null;

    public ?int $from_device_id = null;

    public ?int $from_port = 1;

    public ?int $to_device_id = null;

    public ?int $to_port = 1;

    /**
     * The devices in the current world available to patch (own world only,
     * thanks to the student global scope on Device).
     *
     * @return Collection<int, Device>
     */
    #[Computed]
    public function devices(): Collection
    {
        return Device::orderBy('name')->get();
    }

    /**
     * @return array<int, CableMedium>
     */
    #[Computed]
    public function mediums(): array
    {
        return CableMedium::cases();
    }

    /**
     * Open the form to add a new cable.
     */
    #[On('add-cable')]
    public function openCreate(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $this->resetForm();
        $this->medium = CableMedium::Utp->value;
        $this->from_device_id = $this->devices->first()?->id;
        $this->to_device_id = $this->devices->skip(1)->first()?->id ?? $this->from_device_id;
        $this->showModal = true;
    }

    /**
     * Open the form pre-filled from two clicked ports (click-to-connect),
     * so the student only has to give the cable a number and type.
     */
    #[On('patch-ports')]
    public function openFromPorts(int $fromDeviceId, int $fromPort, int $toDeviceId, int $toPort): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $this->resetForm();
        $this->medium = CableMedium::Utp->value;
        $this->from_device_id = $fromDeviceId;
        $this->from_port = $fromPort;
        $this->to_device_id = $toDeviceId;
        $this->to_port = $toPort;
        $this->showModal = true;
    }

    /**
     * Open the form to edit an existing cable.
     */
    #[On('edit-cable')]
    public function openEdit(int $cableId): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $cable = Cable::findOrFail($cableId);

        $this->cableId = $cable->id;
        $this->label = $cable->label;
        $this->medium = $cable->medium->value;
        $this->color = $cable->color;
        $this->from_device_id = $cable->from_device_id;
        $this->from_port = $cable->from_port;
        $this->to_device_id = $cable->to_device_id;
        $this->to_port = $cable->to_port;
        $this->showModal = true;
    }

    /**
     * Persist the cable. Validates the ports exist on the chosen devices and
     * are not already occupied by another cable.
     */
    public function save(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $validated = $this->validate();

        if (! $this->endpointsAreValid()) {
            return;
        }

        if ($this->cableId !== null) {
            Cable::findOrFail($this->cableId)->update($validated);
        } else {
            Cable::create($validated);
        }

        $this->showModal = false;
        $this->dispatch('cable-saved');

        Flux::toast(variant: 'success', text: __('Kabel opgeslagen.'));
    }

    /**
     * Delete a cable.
     */
    #[On('delete-cable')]
    public function delete(int $cableId): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        Cable::findOrFail($cableId)->delete();

        $this->dispatch('cable-saved');

        Flux::toast(variant: 'success', text: __('Kabel verwijderd.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $studentId = app(CurrentStudent::class)->id();

        return [
            'label' => [
                'required', 'string', 'max:255',
                Rule::unique('cables', 'label')->where('student_id', $studentId)->ignore($this->cableId),
            ],
            'medium' => ['required', Rule::enum(CableMedium::class)],
            'color' => ['nullable', 'string', 'max:255'],
            'from_device_id' => ['required', Rule::exists('devices', 'id')],
            'from_port' => ['required', 'integer', 'min:1'],
            'to_device_id' => ['required', Rule::exists('devices', 'id')],
            'to_port' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Ensure both ports fit on their device, the two ends differ, and neither
     * port is already taken by another cable. Adds errors and returns false.
     */
    protected function endpointsAreValid(): bool
    {
        $valid = true;
        $from = Device::find($this->from_device_id);
        $to = Device::find($this->to_device_id);

        if ($from && $this->from_port > $from->port_count) {
            $this->addError('from_port', __('Poort bestaat niet (:n poorten).', ['n' => $from->port_count]));
            $valid = false;
        }

        if ($to && $this->to_port > $to->port_count) {
            $this->addError('to_port', __('Poort bestaat niet (:n poorten).', ['n' => $to->port_count]));
            $valid = false;
        }

        if ($this->from_device_id === $this->to_device_id && $this->from_port === $this->to_port) {
            $this->addError('to_port', __('Een kabel kan niet op dezelfde poort beginnen en eindigen.'));
            $valid = false;
        }

        foreach ([['from_device_id', 'from_port'], ['to_device_id', 'to_port']] as [$deviceKey, $portKey]) {
            if ($this->portTaken($this->{$deviceKey}, $this->{$portKey})) {
                $this->addError($portKey, __('Deze poort is al bezet door een andere kabel.'));
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Whether another cable already occupies the given device port.
     */
    protected function portTaken(?int $deviceId, ?int $port): bool
    {
        if ($deviceId === null || $port === null) {
            return false;
        }

        return Cable::query()
            ->when($this->cableId, fn ($query) => $query->whereKeyNot($this->cableId))
            ->where(function ($query) use ($deviceId, $port): void {
                $query->where(fn ($q) => $q->where('from_device_id', $deviceId)->where('from_port', $port))
                    ->orWhere(fn ($q) => $q->where('to_device_id', $deviceId)->where('to_port', $port));
            })
            ->exists();
    }

    protected function resetForm(): void
    {
        $this->reset(['cableId', 'label', 'medium', 'color', 'from_device_id', 'from_port', 'to_device_id', 'to_port']);
        $this->resetValidation();
    }
}; ?>

<flux:modal wire:model.self="showModal" class="md:w-[28rem]">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $cableId ? __('Kabel bewerken') : __('Kabel toevoegen') }}
            </flux:heading>
            <flux:subheading>{{ __('Verbind twee poorten en geef de kabel een nummer.') }}</flux:subheading>
        </div>

        <div class="flex gap-4">
            <flux:input wire:model="label" :label="__('Kabelnummer')" placeholder="K-001" required />
            <flux:select wire:model="medium" :label="__('Type')" required>
                @foreach ($this->mediums as $cableMedium)
                    <flux:select.option :value="$cableMedium->value">{{ $cableMedium->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:input wire:model="color" :label="__('Kleur (optioneel)')" placeholder="blauw" />

        <fieldset class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
            <flux:text size="sm" variant="subtle">{{ __('Van') }}</flux:text>
            <div class="flex gap-4">
                <flux:select wire:model="from_device_id" :label="__('Apparaat')" required>
                    @foreach ($this->devices as $device)
                        <flux:select.option :value="$device->id">{{ $device->name }} ({{ $device->port_count }}p)</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="number" wire:model="from_port" :label="__('Poort')" min="1" required />
            </div>
        </fieldset>

        <fieldset class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
            <flux:text size="sm" variant="subtle">{{ __('Naar') }}</flux:text>
            <div class="flex gap-4">
                <flux:select wire:model="to_device_id" :label="__('Apparaat')" required>
                    @foreach ($this->devices as $device)
                        <flux:select.option :value="$device->id">{{ $device->name }} ({{ $device->port_count }}p)</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="number" wire:model="to_port" :label="__('Poort')" min="1" required />
            </div>
        </fieldset>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Annuleren') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" data-test="save-cable-button">
                {{ __('Opslaan') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
