<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function pdf(Report $report): BinaryFileResponse
    {
        Gate::authorize('view', $report);

        abort_unless($report->pdf_path && Storage::disk('public')->exists($report->pdf_path), 404);

        return response()->file(Storage::disk('public')->path($report->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="report-'.$report->id.'.pdf"',
        ]);
    }
}
