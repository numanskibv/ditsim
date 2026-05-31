<?php

namespace App\Enums;

enum Role: string
{
    case Technicus = 'technicus';
    case Leidinggevende = 'leidinggevende';
    case Klant = 'klant';
    case Docent = 'docent';

    /**
     * The human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Technicus => 'Technicus',
            self::Leidinggevende => 'Leidinggevende',
            self::Klant => 'Klant',
            self::Docent => 'Docent',
        };
    }

    /**
     * The authorization abilities granted to this role.
     *
     * These ability names are registered as Gates and form the foundation
     * for the four-eyes and security requirements across every assignment.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Docent => [Ability::ManageScenarios->value],
            self::Leidinggevende => [Ability::ApproveTasks->value],
            self::Technicus => [Ability::ExecuteTasks->value],
            self::Klant => [Ability::CreateReports->value],
        };
    }

    /**
     * Determine whether this role is granted the given ability.
     */
    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities(), true);
    }
}
