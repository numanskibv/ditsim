<?php

namespace App\Http\Controllers;

use App\Support\Manuals;
use Illuminate\Contracts\View\View;

/**
 * Renders a single manual as a clean, print-friendly page (no app chrome) so
 * the teacher can print it to paper or save it as PDF to hand out.
 */
class ManualPrintController extends Controller
{
    public function __invoke(Manuals $manuals, string $slug): View
    {
        abort_unless($manuals->exists($slug), 404);

        return view('manuals.print', [
            'title' => $manuals->label($slug),
            'html' => $manuals->html($slug),
        ]);
    }
}
