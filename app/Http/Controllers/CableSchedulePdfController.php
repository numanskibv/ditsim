<?php

namespace App\Http\Controllers;

use App\Models\Cable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CableSchedulePdfController extends Controller
{
    /**
     * Stream the current student's cable schedule (kabelstaat) as a PDF. The
     * student global scope on Cable limits this to the student's own world.
     */
    public function __invoke(Request $request): SymfonyResponse
    {
        $cables = Cable::with(['fromDevice', 'toDevice', 'lastChangedBy'])
            ->orderBy('label')
            ->get();

        $student = $request->user();

        $pdf = Pdf::loadView('pdf.cable-schedule', [
            'cables' => $cables,
            'student' => $student,
            'date' => now(),
        ]);

        $parts = array_filter([
            'kabelstaat',
            Str::slug($student->name),
            $student->student_number ? Str::slug($student->student_number) : null,
        ]);

        return $pdf->download(implode('-', $parts).'.pdf');
    }
}
