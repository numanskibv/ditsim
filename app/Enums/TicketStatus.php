<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InBehandeling = 'in_behandeling';
    case WachtenOpControle = 'wachten_op_controle';
    case Afgesloten = 'afgesloten';

    /**
     * The human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InBehandeling => 'In behandeling',
            self::WachtenOpControle => 'Wachten op controle',
            self::Afgesloten => 'Afgesloten',
        };
    }

    /**
     * The Flux badge color used to convey this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'sky',
            self::InBehandeling => 'amber',
            self::WachtenOpControle => 'violet',
            self::Afgesloten => 'green',
        };
    }

    /**
     * The status that naturally follows this one in the workflow, if any.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Open => self::InBehandeling,
            self::InBehandeling => self::WachtenOpControle,
            self::WachtenOpControle => self::Afgesloten,
            self::Afgesloten => null,
        };
    }
}
