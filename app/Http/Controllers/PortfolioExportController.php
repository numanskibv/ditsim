<?php

namespace App\Http\Controllers;

use App\Enums\PortfolioAssignment;
use App\Models\Ticket;
use App\Support\PortfolioEvidence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PortfolioExportController extends Controller
{
    public function __construct(protected PortfolioEvidence $evidence) {}

    /**
     * Assemble and download the portfolio evidence PDF for an assignment.
     */
    public function __invoke(Request $request, int $assignment): SymfonyResponse
    {
        $assignment = PortfolioAssignment::tryFrom($assignment)
            ?? abort(Response::HTTP_NOT_FOUND);

        $ticket = $request->integer('ticket')
            ? Ticket::find($request->integer('ticket'))
            : null;

        $evidence = $this->evidence->gather($assignment, $ticket);

        abort_if(
            $evidence['missing'] !== [],
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Portfoliobewijs onvolledig: '.implode(' ', $evidence['missing']),
        );

        $student = $request->user();

        $pdf = Pdf::loadView('pdf.portfolio', [
            'assignment' => $assignment,
            'student' => $student,
            'date' => now(),
            'data' => $evidence['data'],
        ]);

        // Bestandsnaam met naam (+ studentnummer): bv.
        // portfoliobewijs-opdracht-1-jan-jansen-s123456.pdf
        $parts = array_filter([
            'portfoliobewijs',
            'opdracht-'.$assignment->value,
            Str::slug($student->name),
            $student->student_number ? Str::slug($student->student_number) : null,
        ]);

        return $pdf->download(implode('-', $parts).'.pdf');
    }
}
