<?php

namespace App\Observers;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File;

class ReportObserver
{
    /**
     * Handle the Report "created" event.
     */
    public function creating(Report $report)
    {
        // Temporary debug statement
        \Log::info('Creating report with user_id: ' . Auth::id());
        
        $report->user_id = Auth::id();
    }

    public function saved(Report $report): void
    {
        $path = 'reports/'.$report->id.'.pdf';
        $pdf = Pdf::loadView('pdf.report', ['report' => $report->load('user', 'recipients')]);

        Storage::disk('public')->put($path, $this->mergePdfAttachments($pdf->output(), $report));

        if ($report->pdf_path !== $path) {
            $report->forceFill(['pdf_path' => $path])->saveQuietly();
        }
    }

    private function mergePdfAttachments(string $reportPdf, Report $report): string
    {
        $pdfAttachments = collect($report->file_paths ?? [])
            ->map(fn (string $filePath): ?string => $this->resolveAttachmentPath($filePath))
            ->filter(fn (?string $filePath): bool => $filePath !== null && (new File($filePath))->getMimeType() === 'application/pdf')
            ->values();

        if ($pdfAttachments->isEmpty()) {
            return $reportPdf;
        }

        $mergedPdf = new \Imagick();
        $mergedPdf->readImageBlob($reportPdf);

        foreach ($pdfAttachments as $filePath) {
            $attachment = new \Imagick();
            $attachment->readImage($filePath);

            foreach ($attachment as $page) {
                $mergedPdf->addImage($page->getImage());
            }

            $attachment->clear();
            $attachment->destroy();
        }

        $mergedPdf->setImageFormat('pdf');
        $output = $mergedPdf->getImagesBlob();
        $mergedPdf->clear();
        $mergedPdf->destroy();

        return $output;
    }

    private function resolveAttachmentPath(string $filePath): ?string
    {
        $candidates = [
            Storage::disk('public')->path($filePath),
            Storage::disk('public')->path('attachments/'.basename($filePath)),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Handle the Report "updated" event.
     */
    public function updated(Report $report): void
    {
        //
    }

    /**
     * Handle the Report "deleted" event.
     */
    public function deleted(Report $report): void
    {
        //
    }

    /**
     * Handle the Report "restored" event.
     */
    public function restored(Report $report): void
    {
        //
    }

    /**
     * Handle the Report "force deleted" event.
     */
    public function forceDeleted(Report $report): void
    {
        //
    }
}
