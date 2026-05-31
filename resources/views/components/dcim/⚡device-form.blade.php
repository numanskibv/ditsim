<?php

use App\Enums\Ability;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\Role;
use App\Models\Device;
use App\Models\Rack;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $showModal = false;

    public ?int $deviceId = null;

    public ?int $rack_id = null;

    public ?int $owner_id = null;

    public string $name = '';

    public string $type = '';

    public string $status = '';

    public ?int $u_start = null;

    public ?int $u_end = null;

    /**
     * The racks available to mount a device in (also used for moving).
     *
     * @return Collection<int, Rack>
     */
    #[Computed]
    public function racks(): Collection
    {
        return Rack::orderBy('name')->get();
    }

    /**
     * Customers that can own a device.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function owners(): Collection
    {
        return User::where('role', Role::Klant->value)->orderBy('name')->get();
    }

    /**
     * @return array<int, DeviceType>
     */
    #[Computed]
    public function types(): array
    {
        return DeviceType::cases();
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
     * Open the form to add a new device, optionally pre-selecting a rack.
     */
    #[On('add-device')]
    public function openCreate(?int $rackId = null): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $this->resetForm();
        $this->rack_id = $rackId ?? $this->racks->first()?->id;

        // Defaults die overeenkomen met wat de selects tonen, zodat opslaan
        // zonder de velden aan te raken niet faalt op de required-validatie.
        $this->type = DeviceType::Server->value;
        $this->status = DeviceStatus::Actief->value;

        $this->showModal = true;
    }

    /**
     * Open the form to edit (or move) an existing device.
     */
    #[On('edit-device')]
    public function openEdit(int $deviceId): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $device = Device::findOrFail($deviceId);

        $this->deviceId = $device->id;
        $this->rack_id = $device->rack_id;
        $this->owner_id = $device->owner_id;
        $this->name = $device->name;
        $this->type = $device->type->value;
        $this->status = $device->status->value;
        $this->u_start = $device->u_start;
        $this->u_end = $device->u_end;
        $this->showModal = true;
    }

    /**
     * Persist the device. Creating, editing and moving between racks all
     * flow through here; the Device model stamps who changed it and when.
     */
    public function save(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $validated = $this->validate();

        if (! $this->fitsInRack()) {
            return;
        }

        if ($this->deviceId !== null) {
            Device::findOrFail($this->deviceId)->update($validated);
        } else {
            Device::create($validated);
        }

        $this->showModal = false;
        $this->dispatch('device-saved');

        Flux::toast(variant: 'success', text: __('Device opgeslagen.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'rack_id' => ['required', Rule::exists('racks', 'id')],
            'owner_id' => ['nullable', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(DeviceType::class)],
            'status' => ['required', Rule::enum(DeviceStatus::class)],
            'u_start' => ['required', 'integer', 'min:1'],
            'u_end' => ['required', 'integer', 'gte:u_start'],
        ];
    }

    /**
     * Ensure the device stays within the rack height and does not overlap
     * another device on the same U positions. Adds validation errors and
     * returns false when it does not fit.
     */
    protected function fitsInRack(): bool
    {
        $rack = Rack::findOrFail($this->rack_id);
        $fits = true;

        if ($this->u_end > $rack->height_u) {
            $this->addError('u_end', __('Buiten de rackhoogte (:height U).', ['height' => $rack->height_u]));
            $fits = false;
        }

        $overlaps = $rack->devices()
            ->when($this->deviceId, fn ($query) => $query->whereKeyNot($this->deviceId))
            ->where('u_start', '<=', $this->u_end)
            ->where('u_end', '>=', $this->u_start)
            ->exists();

        if ($overlaps) {
            $this->addError('u_start', __('Deze U-posities zijn al bezet in dit rack.'));
            $fits = false;
        }

        return $fits;
    }

    protected function resetForm(): void
    {
        $this->reset(['deviceId', 'rack_id', 'owner_id', 'name', 'type', 'status', 'u_start', 'u_end']);
        $this->resetValidation();
    }
}; ?>

<flux:modal wire:model.self="showModal" class="md:w-96">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $deviceId ? __('Device bewerken') : __('Device toevoegen') }}
            </flux:heading>
            <flux:subheading>{{ __('Plaats, verplaats of wijzig een device in een rack.') }}</flux:subheading>
        </div>

        <flux:input wire:model="name" :label="__('Naam')" required />

        <flux:select wire:model="rack_id" :label="__('Rack')" required>
            @foreach ($this->racks as $rack)
                <flux:select.option :value="$rack->id">{{ $rack->name }} — {{ $rack->location }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="owner_id" :label="__('Eigenaar (klant)')">
            <flux:select.option :value="null">{{ __('— geen —') }}</flux:select.option>
            @foreach ($this->owners as $owner)
                <flux:select.option :value="$owner->id">{{ $owner->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="type" :label="__('Type')" required>
            @foreach ($this->types as $deviceType)
                <flux:select.option :value="$deviceType->value">{{ $deviceType->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="status" :label="__('Status')" required>
            @foreach ($this->statuses as $deviceStatus)
                <flux:select.option :value="$deviceStatus->value">{{ $deviceStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-4">
            <flux:input type="number" wire:model="u_start" :label="__('U-start')" min="1" required />
            <flux:input type="number" wire:model="u_end" :label="__('U-eind')" min="1" required />
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Annuleren') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" data-test="save-device-button">
                {{ __('Opslaan') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
