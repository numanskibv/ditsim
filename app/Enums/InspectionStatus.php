<?php

namespace App\Enums;

enum InspectionStatus: string
{
    case Ok = 'ok';
    case Afwijking = 'afwijking';
    case Actie = 'actie';

    /**
     * The human readable label for the inspection result.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Afwijking => 'Afwijking',
            self::Actie => 'Actie',
        };
    }

    /**
     * The Flux badge color used to convey this result.
     */
    public function color(): string
    {
        return match ($this) {
            self::Ok => 'green',
            self::Afwijking => 'orange',
            self::Actie => 'red',
        };
    }

    /**
     * The device status this finding should push onto a linked device, if
     * any. OK leaves the device untouched.
     */
    public function deviceStatus(): ?DeviceStatus
    {
        return match ($this) {
            self::Ok => null,
            self::Afwijking => DeviceStatus::Waarschuwing,
            self::Actie => DeviceStatus::Storing,
        };
    }
}
