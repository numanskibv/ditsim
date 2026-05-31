<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InstallationPlanPdfController extends Controller
{
    /**
     * Stream the approved installation plan as a PDF.
     */
    public function __invoke(Ticket $ticket): SymfonyResponse
    {
        $plan = $ticket->installationPlan;

        abort_if(
            $plan === null || ! $plan->isApproved(),
            Response::HTTP_FORBIDDEN,
            'Het installatieplan is nog niet goedgekeurd.',
        );

        $plan->loadMissing('approver');

        $pdf = Pdf::loadView('pdf.installation-plan', [
            'ticket' => $ticket,
            'plan' => $plan,
        ]);

        return $pdf->download("installatieplan-{$ticket->number}.pdf");
    }
}
