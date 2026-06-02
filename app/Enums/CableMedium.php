<?php

namespace App\Enums;

enum CableMedium: string
{
    case Utp = 'utp';
    case Fiber = 'fiber';
    case Coax = 'coax';
    case Power = 'power';

    /**
     * The human readable label for the cable medium.
     */
    public function label(): string
    {
        return match ($this) {
            self::Utp => 'UTP (koper)',
            self::Fiber => 'Fiber (glasvezel)',
            self::Coax => 'Coax',
            self::Power => 'Voeding',
        };
    }

    /**
     * Tailwind stroke colour used to draw the cable in the schema.
     */
    public function strokeClass(): string
    {
        return match ($this) {
            self::Utp => 'stroke-blue-500',
            self::Fiber => 'stroke-amber-500',
            self::Coax => 'stroke-purple-500',
            self::Power => 'stroke-red-500',
        };
    }

    /**
     * Tailwind background/border classes for an occupied port dot.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::Utp => 'bg-blue-500 border-blue-600',
            self::Fiber => 'bg-amber-500 border-amber-600',
            self::Coax => 'bg-purple-500 border-purple-600',
            self::Power => 'bg-red-500 border-red-600',
        };
    }
}
