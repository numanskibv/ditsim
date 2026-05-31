<?php

use App\Enums\Ability;
use App\Enums\Role;
use App\Models\Device;
use App\Models\Scenario;
use App\Models\Scopes\StudentScope;
use App\Models\User;
use App\Support\StudentWorld;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Docent control room: assign a starting scenario to each student, reset a
 * student's world, or add a new student. Each student works in their own
 * isolated simulation, so the docent can hand out a different scenario per
 * student.
 */
new #[Title('Studenten')] class extends Component
{
    /**
     * Selected scenario per student id, bound from the row dropdowns.
     *
     * @var array<int, string>
     */
    public array $assign = [];

    /**
     * Selected partner per student id, bound from the row dropdowns.
     *
     * @var array<int, string>
     */
    public array $partner = [];

    /**
     * Studentnummer per student id, bound from the row inputs.
     *
     * @var array<int, string>
     */
    public array $studentNumber = [];

    public string $newName = '';

    public string $newEmail = '';

    public string $newNumber = '';

    /**
     * Typed confirmation for the factory reset (must equal RESET).
     */
    public string $resetConfirmation = '';

    public function mount(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        // Prefill the partner dropdowns and student numbers with current values.
        foreach ($this->students as $student) {
            $this->partner[$student->id] = (string) ($student->partner_id ?? '');
            $this->studentNumber[$student->id] = (string) ($student->student_number ?? '');
        }
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function students(): Collection
    {
        return User::where('role', Role::Technicus->value)
            ->with(['assignedScenario', 'partner'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Device count per student world, keyed by student id.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function deviceCounts(): array
    {
        return Device::withoutGlobalScope(StudentScope::class)
            ->selectRaw('student_id, count(*) as aggregate')
            ->whereNotNull('student_id')
            ->groupBy('student_id')
            ->pluck('aggregate', 'student_id')
            ->all();
    }

    /**
     * Provisioning scenarios that can build a starting world.
     *
     * @return Collection<int, Scenario>
     */
    #[Computed]
    public function scenarios(): Collection
    {
        return Scenario::orderBy('name')->get()->filter->isProvisioning()->values();
    }

    /**
     * Assign a scenario to a student: wipe their world and rebuild it from the
     * scenario blueprint, recording the assignment.
     */
    public function assignScenario(int $studentId): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        $scenarioId = $this->assign[$studentId] ?? null;

        $validated = validator(
            ['scenario' => $scenarioId, 'student' => $studentId],
            [
                'scenario' => ['required', Rule::exists('scenarios', 'id')],
                'student' => ['required', Rule::exists('users', 'id')],
            ],
        )->validate();

        $scenario = Scenario::findOrFail($validated['scenario']);

        app(StudentWorld::class)->wipe($studentId);

        User::whereKey($studentId)->update(['assigned_scenario_id' => $scenario->id]);

        $scenario->applyTo($studentId);

        Flux::toast(variant: 'success', text: __('Scenario toegewezen en wereld opgebouwd.'));
    }

    /**
     * Couple two students together (mutually), so each acts as the
     * leidinggevende/klant counter-role in the other's world. Choosing the empty
     * option uncouples the student (and their former partner).
     */
    public function setPartner(int $studentId): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        $partnerId = ($this->partner[$studentId] ?? '') === '' ? null : (int) $this->partner[$studentId];

        validator(
            ['student' => $studentId, 'partner' => $partnerId],
            [
                'student' => ['required', Rule::exists('users', 'id')],
                'partner' => ['nullable', 'different:student', Rule::exists('users', 'id')],
            ],
        )->validate();

        // Free both students from any previous couple, then link them mutually.
        User::where('partner_id', $studentId)->update(['partner_id' => null]);
        User::whereKey($studentId)->update(['partner_id' => $partnerId]);

        if ($partnerId !== null) {
            User::where('partner_id', $partnerId)->whereKeyNot($studentId)->update(['partner_id' => null]);
            User::whereKey($partnerId)->update(['partner_id' => $studentId]);
        }

        Flux::toast(variant: 'success', text: __('Koppel bijgewerkt.'));
    }

    /**
     * Reset a student's world: wipe it and re-apply the assigned scenario.
     */
    public function resetWorld(int $studentId): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        app(StudentWorld::class)->wipe($studentId);

        $student = User::findOrFail($studentId);

        if ($student->assigned_scenario_id !== null) {
            Scenario::find($student->assigned_scenario_id)?->applyTo($studentId);
        }

        Flux::toast(variant: 'success', text: __('Wereld van student gereset.'));
    }

    /**
     * Add a new student-technicus account.
     */
    public function createStudent(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newNumber' => ['required', 'string', 'max:50'],
            'newEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);

        User::create([
            'name' => $validated['newName'],
            'student_number' => $validated['newNumber'],
            'email' => $validated['newEmail'],
            'password' => Hash::make(config('datacenter.default_password')),
            'role' => Role::Technicus,
        ])->forceFill(['email_verified_at' => now()])->save();

        $this->reset(['newName', 'newNumber', 'newEmail']);

        Flux::toast(variant: 'success', text: __('Student toegevoegd met het standaardwachtwoord.'));
    }

    /**
     * Set or correct a student's number from the list.
     */
    public function saveStudentNumber(int $studentId): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        $number = trim($this->studentNumber[$studentId] ?? '');

        validator(['number' => $number], ['number' => ['required', 'string', 'max:50']])->validate();

        User::whereKey($studentId)->update(['student_number' => $number]);

        Flux::toast(variant: 'success', text: __('Studentnummer opgeslagen.'));
    }

    /**
     * Factory reset the whole environment: wipe all data and reseed the demo.
     * Requires typing RESET. Afterwards everyone is logged out; the teacher
     * signs back in with the reseeded docent account.
     */
    public function factoryReset(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        if ($this->resetConfirmation !== 'RESET') {
            $this->addError('resetConfirmation', __('Typ RESET (in hoofdletters) om te bevestigen.'));

            return;
        }

        Artisan::call('simulate:reset');

        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'));
    }

    /**
     * Remove all simulation/world data (demo content and any student work),
     * keeping accounts, couples and the scenario library.
     */
    public function clearDemoData(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        app(StudentWorld::class)->clearAll();

        Flux::modal('confirm-clear-demo')->close();

        Flux::toast(variant: 'success', text: __('Demodata verwijderd. Accounts en scenario\'s zijn behouden.'));
    }

    /**
     * (Re)import the MediCloud demo data for testing. Rebuilds the demo world
     * (first technicus); other students, accounts and scenarios stay intact.
     */
    public function importDemoData(): void
    {
        $this->authorize(Ability::ManageScenarios->value);

        Artisan::call('simulate:import-demo');

        Flux::modal('confirm-import-demo')->close();

        Flux::toast(variant: 'success', text: __('Demodata (opnieuw) geïmporteerd.'));
    }
}; ?>

<section class="w-full max-w-5xl space-y-8">
    <div>
        <flux:heading size="xl">{{ __('Studenten beheren') }}</flux:heading>
        <flux:subheading>{{ __('Wijs elke student een eigen startscenario toe. Iedere student werkt in een eigen, geïsoleerde simulatie.') }}</flux:subheading>
    </div>

    {{-- Add a student --}}
    <flux:card class="space-y-4">
        <flux:heading size="lg">{{ __('Student toevoegen') }}</flux:heading>
        <div class="flex flex-wrap items-end gap-3">
            <flux:input wire:model="newName" :label="__('Naam')" class="max-w-xs" />
            <flux:input wire:model="newNumber" :label="__('Studentnummer')" class="max-w-40" />
            <flux:input wire:model="newEmail" type="email" :label="__('E-mail')" class="max-w-xs" />
            <flux:button variant="primary" icon="user-plus" wire:click="createStudent" data-test="create-student-button">
                {{ __('Toevoegen') }}
            </flux:button>
        </div>
        @error('newName') <flux:text color="red">{{ $message }}</flux:text> @enderror
        @error('newNumber') <flux:text color="red">{{ $message }}</flux:text> @enderror
        @error('newEmail') <flux:text color="red">{{ $message }}</flux:text> @enderror
    </flux:card>

    {{-- Students table --}}
    <flux:card class="space-y-4">
        <flux:heading size="lg">{{ __('Studenten') }}</flux:heading>

        @forelse ($this->students as $student)
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-100 pb-4 last:border-b-0 dark:border-zinc-800" wire:key="student-{{ $student->id }}">
                <div class="min-w-48">
                    <flux:text variant="strong">{{ $student->name }}@if ($student->student_number) <span class="font-normal opacity-70">· {{ $student->student_number }}</span>@endif</flux:text>
                    <flux:text size="sm" variant="subtle">{{ $student->email }}</flux:text>
                    <flux:text size="sm" variant="subtle">
                        {{ __('Devices in wereld:') }} {{ $this->deviceCounts[$student->id] ?? 0 }}
                        @if ($student->assignedScenario)
                            · {{ __('Scenario:') }} {{ $student->assignedScenario->name }}
                        @endif
                        · {{ __('Partner:') }} {{ $student->partner?->name ?? __('geen') }}
                    </flux:text>
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <div class="flex items-end gap-1">
                        <flux:input wire:model="studentNumber.{{ $student->id }}" :label="__('Studentnr.')" class="max-w-32" />
                        <flux:button variant="filled" icon="check" wire:click="saveStudentNumber({{ $student->id }})" data-test="save-number-{{ $student->id }}" />
                    </div>

                    <flux:select wire:model="partner.{{ $student->id }}" :label="__('Partner (tegenrol)')" class="max-w-xs">
                        <flux:select.option value="">{{ __('— geen partner —') }}</flux:select.option>
                        @foreach ($this->students as $candidate)
                            @if ($candidate->id !== $student->id)
                                <flux:select.option :value="(string) $candidate->id">{{ $candidate->name }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>

                    <flux:button variant="filled" icon="users" wire:click="setPartner({{ $student->id }})" data-test="couple-{{ $student->id }}">
                        {{ __('Koppelen') }}
                    </flux:button>

                    <flux:select wire:model="assign.{{ $student->id }}" :label="__('Startscenario')" class="max-w-xs">
                        <flux:select.option value="">{{ __('— kies scenario —') }}</flux:select.option>
                        @foreach ($this->scenarios as $scenario)
                            <flux:select.option :value="(string) $scenario->id">{{ $scenario->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button variant="primary" icon="play" wire:click="assignScenario({{ $student->id }})" data-test="assign-{{ $student->id }}">
                        {{ __('Toewijzen & starten') }}
                    </flux:button>

                    <flux:button variant="subtle" icon="arrow-path" wire:click="resetWorld({{ $student->id }})" data-test="reset-{{ $student->id }}">
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            </div>
        @empty
            <flux:text size="sm" variant="subtle">{{ __('Nog geen studenten.') }}</flux:text>
        @endforelse
    </flux:card>

    {{-- Demodata beheren: verwijderen (lege werelden) of (opnieuw) importeren (testen). --}}
    <flux:card class="space-y-4 border-amber-200 dark:border-amber-500/30">
        <div>
            <flux:heading size="lg" class="text-amber-700 dark:text-amber-400">{{ __('Demodata') }}</flux:heading>
            <flux:subheading>
                {{ __('Verwijderen wist alle simulatie-/wereldgegevens van álle studenten (de demo én eventueel werk); handig om met een schone klas te starten. Importeren zet de MediCloud-demo opnieuw klaar om mee te testen. In beide gevallen blijven accounts, koppels en scenario\'s behouden.') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:modal.trigger name="confirm-clear-demo">
                <flux:button variant="filled" icon="trash" data-test="open-clear-demo">
                    {{ __('Demodata verwijderen…') }}
                </flux:button>
            </flux:modal.trigger>

            <flux:modal.trigger name="confirm-import-demo">
                <flux:button variant="filled" icon="arrow-down-tray" data-test="open-import-demo">
                    {{ __('Demodata importeren…') }}
                </flux:button>
            </flux:modal.trigger>
        </div>
    </flux:card>

    <flux:modal name="confirm-clear-demo" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Demodata verwijderen?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Alle racks, devices, tickets en overig wereld-/bewijsmateriaal van alle studenten worden verwijderd. Accounts, koppels en scenario\'s blijven staan. Dit kan niet ongedaan worden gemaakt.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Annuleren') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" icon="trash" wire:click="clearDemoData" data-test="confirm-clear-demo">
                    {{ __('Ja, verwijderen') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="confirm-import-demo" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Demodata importeren?') }}</flux:heading>
                <flux:subheading>
                    {{ __('De MediCloud-demo (rack R03, apparatuur, voorbeeldtickets, bezoekers, inspectie) wordt opnieuw klaargezet in de demo-omgeving. Andere studenten, accounts en scenario\'s blijven ongemoeid. Veilig om te herhalen.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Annuleren') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" icon="arrow-down-tray" wire:click="importDemoData" data-test="confirm-import-demo">
                    {{ __('Ja, importeren') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Danger zone: full factory reset (for cloud, where there is no terminal). --}}
    <flux:card class="space-y-4 border-red-200 dark:border-red-500/30">
        <div>
            <flux:heading size="lg" class="text-red-700 dark:text-red-400">{{ __('Volledige reset (fabrieksinstelling)') }}</flux:heading>
            <flux:subheading>
                {{ __('Wist ALLE data: alle studenten, koppels, werelden en bewijs, en zet de demo-omgeving en standaardaccounts terug. Je wordt hierna uitgelogd en logt weer in als docent.') }}
            </flux:subheading>
        </div>

        <flux:modal.trigger name="confirm-factory-reset">
            <flux:button variant="danger" icon="exclamation-triangle" data-test="open-factory-reset">
                {{ __('Volledige reset…') }}
            </flux:button>
        </flux:modal.trigger>
    </flux:card>

    <flux:modal name="confirm-factory-reset" :show="$errors->has('resetConfirmation')" focusable class="max-w-lg">
        <form wire:submit="factoryReset" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Weet je zeker dat je de hele omgeving wilt resetten?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Deze actie kan niet ongedaan worden gemaakt. Alle data wordt definitief verwijderd en de demo-omgeving wordt opnieuw opgebouwd. Typ RESET om te bevestigen.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="resetConfirmation" :label="__('Typ RESET om te bevestigen')" placeholder="RESET" />
            @error('resetConfirmation') <flux:text color="red">{{ $message }}</flux:text> @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Annuleren') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" data-test="confirm-factory-reset">
                    {{ __('Definitief resetten') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
