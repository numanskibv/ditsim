<?php

namespace App\Enums;

enum PortfolioAssignment: int
{
    case One = 1;
    case Two = 2;
    case Three = 3;
    case Four = 4;
    case Five = 5;
    case Six = 6;

    /**
     * The short title of the assignment.
     */
    public function title(): string
    {
        return match ($this) {
            self::One => 'Installatie uitvoeren volgens goedgekeurd plan',
            self::Two => 'Monitoren en bewaken (NOC)',
            self::Three => 'Onderhoud en inspectie uitvoeren',
            self::Four => 'Storing lokaliseren en verhelpen',
            self::Five => 'Incident afhandelen en terugkoppelen',
            self::Six => 'Bezoek begeleiden en fysieke toegang beveiligen',
        };
    }

    /**
     * The work process this assignment provides evidence for.
     */
    public function werkproces(): string
    {
        return match ($this) {
            self::One => 'Voert wijzigingen/installaties uit en bewaakt de voortgang',
            self::Two => 'Bewaakt en monitort de ICT-infrastructuur',
            self::Three => 'Voert preventief onderhoud en inspecties uit',
            self::Four => 'Lokaliseert en verhelpt verstoringen',
            self::Five => 'Handelt incidenten en verzoeken af en koppelt terug',
            self::Six => 'Begeleidt bezoekers en beveiligt fysieke toegang',
        };
    }

    /**
     * Whether the export needs a specific ticket to be selected.
     */
    public function requiresTicket(): bool
    {
        return in_array($this, [self::One, self::Four, self::Five], true);
    }
}
