<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Classes;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentController extends Controller
{
    public function pdf(): Response
    {
        $user = auth()->user();
        abort_unless($user?->hasUnrestrictedAccess() || $user?->can('view_any_enrollment'), 403);

        $classNames = Classes::query()->pluck('name', 'id');

        $enrollments = Enrollment::query()
            ->accessibleTo($user)
            ->with(['yearSession', 'term'])
            ->latest()
            ->get()
            ->map(function (Enrollment $enrollment) use ($classNames): Enrollment {
                $enrollment->class_breakdown = collect($enrollment->class_breakdown ?? [])
                    ->map(function (array $class) use ($classNames): array {
                        $class['class_name'] = isset($class['class_id'])
                            ? $classNames->get($class['class_id'])
                            : null;

                        return $class;
                    })
                    ->all();

                return $enrollment;
            });

        return Pdf::loadView('pdf.enrollments', compact('enrollments'))
            ->setPaper('a4', 'landscape')
            ->stream('enrollment-report.pdf');
    }

    public function excel(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user?->hasUnrestrictedAccess() || $user?->can('view_any_enrollment'), 403);

        $enrollments = Enrollment::query()
            ->accessibleTo($user)
            ->with(['yearSession', 'term'])
            ->latest()
            ->get();
        $classNames = Classes::query()->pluck('name', 'id');

        return response()->streamDownload(function () use ($enrollments, $classNames): void {
            $writer = new XlsxWriter();
            $writer->openToBrowser('enrollment-report.xlsx');
            $writer->addRow(Row::fromValues([
                'Branch',
                'Academic Year',
                'Term',
                'Total Learners',
                'Class',
                'Boys',
                'Girls',
                'Class Total',
                'New Admissions',
                'Learners Left',
                'Comment',
            ]));

            foreach ($enrollments as $enrollment) {
                $classes = $enrollment->class_breakdown ?? [];

                if ($classes === []) {
                    $writer->addRow(Row::fromValues([
                        $enrollment->branch,
                        $enrollment->yearSession?->name,
                        $enrollment->term?->name,
                        $enrollment->total_learners,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        $enrollment->comment,
                    ]));

                    continue;
                }

                foreach ($classes as $class) {
                    $className = $class['class_name']
                        ?? $classNames->get($class['class_id'] ?? null)
                        ?? 'Class';

                    $writer->addRow(Row::fromValues([
                        $enrollment->branch,
                        $enrollment->yearSession?->name,
                        $enrollment->term?->name,
                        $enrollment->total_learners,
                        $className,
                        (int) ($class['boys'] ?? 0),
                        (int) ($class['girls'] ?? 0),
                        (int) ($class['total'] ?? ((int) ($class['boys'] ?? 0) + (int) ($class['girls'] ?? 0))),
                        (int) ($class['new_admissions'] ?? 0),
                        (int) ($class['departures'] ?? 0),
                        $enrollment->comment,
                    ]));
                }
            }

            $writer->close();
        }, 'enrollment-report.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
