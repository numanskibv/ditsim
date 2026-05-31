<?php

namespace App\Enums;

enum TicketType: string
{
    case Incident = 'incident';
    case Change = 'change';
    case ServiceRequest = 'service_request';

    /**
     * The human readable label for the ticket type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Incident => 'Incident',
            self::Change => 'Wijziging',
            self::ServiceRequest => 'Serviceverzoek',
        };
    }

    /**
     * The prefix used in the generated ticket number (e.g. INC-2026-0001).
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Incident => 'INC',
            self::Change => 'CHG',
            self::ServiceRequest => 'SR',
        };
    }
}
