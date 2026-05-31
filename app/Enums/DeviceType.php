<?php

namespace App\Enums;

enum DeviceType: string
{
    case Server = 'server';
    case Switch = 'switch';
    case Router = 'router';
    case Storage = 'storage';
    case Firewall = 'firewall';

    /**
     * The human readable label for the device type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Server => 'Server',
            self::Switch => 'Switch',
            self::Router => 'Router',
            self::Storage => 'Storage',
            self::Firewall => 'Firewall',
        };
    }
}
