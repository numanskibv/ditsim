<?php

use App\Enums\Ability;
use App\Models\Rack;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $showModal = false;

    public string $name = '';

    public string $location = '';

    public ?int $height_u = 42;

    /**
     * Open the form to add a new rack in the current student's world.
     */
    #[On('add-rack')]
    public function openCreate(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Persist a new rack. The Rack model stamps it to the current student's
     * world automatically (BelongsToStudent), so it stays isolated.
     */
    public function save(): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        $validated = $this->validate();

        Rack::create($validated);

        $this->showModal = false;
        $this->dispatch('rack-saved');

        Flux::toast(variant: 'success', text: __('Rack toegevoegd.'));
    }

    /**
     * Delete a rack and (via cascade) every device mounted in it. The student
     * global scope keeps this limited to racks the technicus may see.
     */
    #[On('delete-rack')]
    public function delete(int $rackId): void
    {
        $this->authorize(Ability::ExecuteTasks->value);

        Rack::findOrFail($rackId)->delete();

        $this->dispatch('rack-saved');

        Flux::toast(variant: 'success', text: __('Rack verwijderd.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'height_u' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['name', 'location', 'height_u']);
        $this->resetValidation();
    }
}; ?>

<flux:modal wire:model.self="showModal" class="md:w-96">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Rack toevoegen') }}</flux:heading>
            <flux:subheading>{{ __('Plaats een nieuw rack in je eigen datacenter.') }}</flux:subheading>
        </div>

        <flux:input wire:model="name" :label="__('Naam')" placeholder="R04" required />

        <flux:input wire:model="location" :label="__('Locatie')" placeholder="DC-Utrecht" required />

        <flux:input type="number" wire:model="height_u" :label="__('Hoogte (U)')" min="1" max="60" required />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Annuleren') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" data-test="save-rack-button">
                {{ __('Opslaan') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
