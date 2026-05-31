<?php

namespace App\Livewire\Concerns;

use App\Support\CurrentStudent;
use Illuminate\Validation\ValidationException;

/**
 * Guards write actions of shared roles against the "overview" state.
 *
 * A technicus always resolves to a concrete world (their own), so this never
 * blocks them. A shared instructor role (leidinggevende/klant/docent) with no
 * "Actieve student" chosen resolves to null — acting then would land records in
 * the shared layer or in an ambiguous world, so we refuse and ask them to pick
 * a student first.
 */
trait InteractsWithStudentWorld
{
    protected function requireActiveStudent(): void
    {
        if (app(CurrentStudent::class)->id() === null) {
            throw ValidationException::withMessages([
                'student_world' => __('Kies eerst een actieve student (bovenin) voordat je dit doet.'),
            ]);
        }
    }
}
