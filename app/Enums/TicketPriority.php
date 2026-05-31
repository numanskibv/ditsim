<?php

namespace App\Enums;

enum TicketPriority: string
{
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';

    /**
     * The human readable label for the priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::P1 => 'P1 — Kritiek',
            self::P2 => 'P2 — Hoog',
            self::P3 => 'P3 — Normaal',
        };
    }

    /**
     * The SLA resolution target in minutes for this priority.
     */
    public function slaMinutes(): int
    {
        return match ($this) {
            self::P1 => 60,
            self::P2 => 240,
            self::P3 => 480,
        };
    }

    /**
     * The Flux badge color used to convey this priority.
     */
    public function color(): string
    {
        return match ($this) {
            self::P1 => 'red',
            self::P2 => 'orange',
            self::P3 => 'zinc',
        };
    }
}
