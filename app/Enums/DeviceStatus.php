<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case Actief = 'actief';
    case Waarschuwing = 'waarschuwing';
    case Storing = 'storing';
    case Offline = 'offline';

    /**
     * The human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Actief => 'Actief',
            self::Waarschuwing => 'Waarschuwing',
            self::Storing => 'Storing',
            self::Offline => 'Offline',
        };
    }

    /**
     * The Flux badge color used to convey this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Actief => 'green',
            self::Waarschuwing => 'orange',
            self::Storing => 'red',
            self::Offline => 'zinc',
        };
    }

    /**
     * Whether reaching this status should raise an alert in the NOC panel.
     */
    public function isAlerting(): bool
    {
        return in_array($this, [self::Waarschuwing, self::Storing], true);
    }

    /**
     * Tailwind background classes for a device block in the rack grid.
     */
    public function cellClasses(): string
    {
        return match ($this) {
            self::Actief => 'bg-green-500/80 border-green-600 text-white',
            self::Waarschuwing => 'bg-orange-500/80 border-orange-600 text-white',
            self::Storing => 'bg-red-500/80 border-red-600 text-white',
            self::Offline => 'bg-zinc-400/80 border-zinc-500 text-white dark:bg-zinc-600/80 dark:border-zinc-500',
        };
    }
}
