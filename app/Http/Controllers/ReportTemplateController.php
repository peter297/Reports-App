<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportTemplateController extends Controller
{
    public function download(ReportTemplate $reportTemplate): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($reportTemplate->file_path), 404);

        $filename = $reportTemplate->name.'.'.$reportTemplate->extension;

        return Storage::disk('public')->download(
            $reportTemplate->file_path,
            $filename,
        );
    }
}
