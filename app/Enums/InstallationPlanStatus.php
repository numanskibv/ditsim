<?php

namespace App\Enums;

enum InstallationPlanStatus: string
{
    case Concept = 'concept';
    case Goedgekeurd = 'goedgekeurd';
    case Afgekeurd = 'afgekeurd';

    /**
     * The human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Concept => 'Concept',
            self::Goedgekeurd => 'Goedgekeurd',
            self::Afgekeurd => 'Afgekeurd',
        };
    }

    /**
     * The Flux badge color used to convey this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Concept => 'zinc',
            self::Goedgekeurd => 'green',
            self::Afgekeurd => 'red',
        };
    }
}
