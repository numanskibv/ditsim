<?php

use App\Enums\Ability;
use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Livewire\Concerns\InteractsWithStudentWorld;
use App\Models\Device;
use App\Models\Ticket;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use InteractsWithStudentWorld;

    public bool $showModal = false;

    public ?int $ticketId = null;

    public string $type = '';

    public string $title = '';

    public string $description = '';

    public string $priority = '';

    public ?int $device_id = null;

    public ?int $assigned_to = null;

    /**
     * @return Collection<int, Device>
     */
    #[Computed]
    public function devices(): Collection
    {
        return Device::with('rack')->orderBy('name')->get();
    }

    /**
     * Executors that a ticket can be assigned to.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function technici(): Collection
    {
        return User::where('role', Role::Technicus->value)->orderBy('name')->get();
    }

    /**
     * @return array<int, TicketType>
     */
    #[Computed]
    public function types(): array
    {
        return TicketType::cases();
    }

    /**
     * @return array<int, TicketPriority>
     */
    #[Computed]
    public function priorities(): array
    {
        return TicketPriority::cases();
    }

    #[On('create-ticket')]
    public function openCreate(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->type = TicketType::Incident->value;
        $this->priority = TicketPriority::P3->value;
        $this->showModal = true;
    }

    #[On('edit-ticket')]
    public function openEdit(int $ticketId): void
    {
        $this->authorizeManage();

        $ticket = Ticket::findOrFail($ticketId);

        $this->ticketId = $ticket->id;
        $this->type = $ticket->type->value;
        $this->title = $ticket->title;
        $this->description = (string) $ticket->description;
        $this->priority = $ticket->priority->value;
        $this->device_id = $ticket->device_id;
        $this->assigned_to = $ticket->assigned_to;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeManage();
        $this->requireActiveStudent();

        $validated = $this->validate();

        if ($this->ticketId !== null) {
            Ticket::findOrFail($this->ticketId)->update($validated);
        } else {
            Ticket::create($validated);
        }

        $this->showModal = false;
        $this->dispatch('ticket-saved');

        Flux::toast(variant: 'success', text: __('Ticket opgeslagen.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TicketType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'device_id' => ['nullable', Rule::exists('devices', 'id')],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
        ];
    }

    /**
     * Creating and editing tickets is open to customers (who file reports)
     * and technicians (who handle them).
     */
    protected function authorizeManage(): void
    {
        abort_unless(
            Gate::any([Ability::CreateReports->value, Ability::ExecuteTasks->value]),
            403,
        );
    }

    protected function resetForm(): void
    {
        $this->reset(['ticketId', 'type', 'title', 'description', 'priority', 'device_id', 'assigned_to']);
        $this->resetValidation();
    }
}; ?>

<flux:modal wire:model.self="showModal" class="md:w-lg">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $ticketId ? __('Ticket bewerken') : __('Nieuw ticket') }}
            </flux:heading>
            <flux:subheading>{{ __('Registreer een incident, wijziging of serviceverzoek.') }}</flux:subheading>
        </div>

        <flux:input wire:model="title" :label="__('Titel')" required />

        <flux:textarea wire:model="description" :label="__('Omschrijving')" rows="3" />

        <div class="flex gap-4">
            <flux:select wire:model="type" :label="__('Type')" required>
                @foreach ($this->types as $ticketType)
                    <flux:select.option :value="$ticketType->value">{{ $ticketType->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="priority" :label="__('Prioriteit')" required>
                @foreach ($this->priorities as $ticketPriority)
                    <flux:select.option :value="$ticketPriority->value">{{ $ticketPriority->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:select wire:model="device_id" :label="__('Gekoppeld device')">
            <flux:select.option :value="null">{{ __('— geen —') }}</flux:select.option>
            @foreach ($this->devices as $device)
                <flux:select.option :value="$device->id">{{ $device->name }} ({{ $device->rack?->name }})</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="assigned_to" :label="__('Toegewezen aan')">
            <flux:select.option :value="null">{{ __('— niet toegewezen —') }}</flux:select.option>
            @foreach ($this->technici as $technicus)
                <flux:select.option :value="$technicus->id">{{ $technicus->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Annuleren') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" data-test="save-ticket-button">
                {{ __('Opslaan') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
