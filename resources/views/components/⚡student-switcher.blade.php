<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\CurrentStudent;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Lets the user choose which student's world to act within.
 *
 * - A technicus only sees their own world plus (if coupled) their partner's
 *   world — never a stranger's.
 * - A shared instructor role (docent/leidinggevende/klant) sees every student
 *   plus an "all students" overview option.
 *
 * The switcher hides itself when there is nothing to choose between.
 */
new class extends Component
{
    public string $activeStudentId = '';

    public function mount(): void
    {
        $this->activeStudentId = (string) (app(CurrentStudent::class)->id() ?? '');
    }

    /**
     * The selectable worlds as [value, label] pairs.
     *
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function options(): array
    {
        $user = auth()->user();

        if ($user?->hasRole(Role::Technicus)) {
            $options = [['value' => (string) $user->id, 'label' => __('Mijn omgeving')]];

            if ($user->partner) {
                $options[] = [
                    'value' => (string) $user->partner->id,
                    'label' => __('Partner: :name', ['name' => $user->partner->name]),
                ];
            }

            return $options;
        }

        $options = [['value' => '', 'label' => __('Alle studenten (overzicht)')]];

        foreach (User::where('role', Role::Technicus->value)->orderBy('name')->get(['id', 'name']) as $student) {
            $options[] = ['value' => (string) $student->id, 'label' => $student->name];
        }

        return $options;
    }

    /**
     * Persist the chosen world and reload so scoped data reflects it.
     */
    public function updatedActiveStudentId(string $value): void
    {
        app(CurrentStudent::class)->setActive($value === '' ? null : (int) $value);

        $this->redirect(url()->previous(), navigate: true);
    }
}; ?>

<div class="px-2 py-2">
    @if (count($this->options) > 1)
        <flux:select
            wire:model.live="activeStudentId"
            size="sm"
            :label="__('Actieve student')"
            data-test="student-switcher"
        >
            @foreach ($this->options as $option)
                <flux:select.option :value="$option['value']">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
    @endif
</div>
