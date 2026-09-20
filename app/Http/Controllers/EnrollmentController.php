<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Enrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
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
            $writer = new XlsxWriter;
            $writer->openToBrowser('enrollment-report.xlsx');

            // --- Styles ---
            $titleStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(16)
                ->setFontColor(Color::toARGB(Color::DARK_BLUE))
                ->setCellAlignment(CellAlignment::CENTER);

            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(11)
                ->setFontColor(Color::toARGB(Color::WHITE))
                ->setBackgroundColor(Color::toARGB(Color::DARK_BLUE))
                ->setCellAlignment(CellAlignment::CENTER)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $dataStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setCellAlignment(CellAlignment::LEFT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $dataStyleRight = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $boldDataStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setCellAlignment(CellAlignment::LEFT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $boldDataStyleRight = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $totalStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $totalLabelStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::LEFT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $successStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::GREEN))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $dangerStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::RED))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $totalSuccessStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::GREEN))
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $totalDangerStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::RED))
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            // --- Title Row ---
            $writer->addRow(Row::fromValues([
                'Alameen Academy - Enrollment Report',
            ], $titleStyle));

            // Empty spacer row
            $writer->addRow(Row::fromValues(['']));

            // --- Header Row ---
            $headers = ['Branch', 'Academic Year', 'Term', 'Total Learners', 'Class', 'Boys', 'Girls', 'Class Total', 'Admitted', 'Left', 'Comment'];
            $writer->addRow(Row::fromValues($headers, $headerStyle));

            // --- Data Rows ---
            foreach ($enrollments as $enrollment) {
                $classes = $enrollment->class_breakdown ?? [];

                if ($classes === []) {
                    $writer->addRow(Row::fromValuesWithStyles(
                        [
                            $enrollment->branch,
                            $enrollment->yearSession?->name ?? '',
                            $enrollment->term?->name ?? '',
                            $enrollment->total_learners,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $enrollment->comment ?? '',
                        ],
                        $dataStyle,
                        [
                            3 => $boldDataStyleRight,
                        ],
                    ));

                    continue;
                }

                foreach ($classes as $class) {
                    $className = $class['class_name']
                        ?? $classNames->get($class['class_id'] ?? null)
                        ?? 'Class';
                    $boys = (int) ($class['boys'] ?? 0);
                    $girls = (int) ($class['girls'] ?? 0);
                    $total = (int) ($class['total'] ?? $boys + $girls);
                    $admitted = (int) ($class['new_admissions'] ?? 0);
                    $left = (int) ($class['departures'] ?? 0);

                    $writer->addRow(Row::fromValuesWithStyles(
                        [
                            $enrollment->branch,
                            $enrollment->yearSession?->name ?? '',
                            $enrollment->term?->name ?? '',
                            $enrollment->total_learners,
                            $className,
                            $boys,
                            $girls,
                            $total,
                            $admitted,
                            $left,
                            $enrollment->comment ?? '',
                        ],
                        $dataStyle,
                        [
                            3 => $boldDataStyleRight,
                            4 => $dataStyle,
                            5 => $dataStyleRight,
                            6 => $dataStyleRight,
                            7 => $boldDataStyleRight,
                            8 => $successStyle,
                            9 => $dangerStyle,
                        ],
                    ));
                }

                // Subtotal row per enrollment (only if multiple classes)
                if (count($classes) > 1) {
                    $totalBoys = collect($classes)->sum('boys');
                    $totalGirls = collect($classes)->sum('girls');
                    $totalStudents = collect($classes)->sum('total');
                    $totalAdmitted = collect($classes)->sum('new_admissions');
                    $totalLeft = collect($classes)->sum('departures');

                    $writer->addRow(Row::fromValuesWithStyles(
                        [
                            '',
                            '',
                            '',
                            '',
                            'Subtotal',
                            $totalBoys,
                            $totalGirls,
                            $totalStudents,
                            $totalAdmitted,
                            $totalLeft,
                            '',
                        ],
                        $totalLabelStyle,
                        [
                            5 => $totalStyle,
                            6 => $totalStyle,
                            7 => $totalStyle,
                            8 => $totalSuccessStyle,
                            9 => $totalDangerStyle,
                        ],
                    ));
                }
            }

            // --- Grand Total Row ---
            $grandTotalBoys = 0;
            $grandTotalGirls = 0;
            $grandTotalStudents = 0;
            $grandTotalAdmitted = 0;
            $grandTotalLeft = 0;

            foreach ($enrollments as $enrollment) {
                $classes = $enrollment->class_breakdown ?? [];
                $grandTotalBoys += collect($classes)->sum('boys');
                $grandTotalGirls += collect($classes)->sum('girls');
                $grandTotalStudents += collect($classes)->sum('total');
                $grandTotalAdmitted += collect($classes)->sum('new_admissions');
                $grandTotalLeft += collect($classes)->sum('departures');
            }

            $grandTotalStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(11)
                ->setBackgroundColor(Color::toARGB('E5E7EB'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $grandTotalLabelStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(11)
                ->setBackgroundColor(Color::toARGB('E5E7EB'))
                ->setCellAlignment(CellAlignment::LEFT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $grandTotalSuccessStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(11)
                ->setFontColor(Color::toARGB(Color::GREEN))
                ->setBackgroundColor(Color::toARGB('E5E7EB'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $grandTotalDangerStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(11)
                ->setFontColor(Color::toARGB(Color::RED))
                ->setBackgroundColor(Color::toARGB('E5E7EB'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $writer->addRow(Row::fromValuesWithStyles(
                [
                    '',
                    '',
                    '',
                    '',
                    'GRAND TOTAL',
                    $grandTotalBoys,
                    $grandTotalGirls,
                    $grandTotalStudents,
                    $grandTotalAdmitted,
                    $grandTotalLeft,
                    '',
                ],
                $grandTotalLabelStyle,
                [
                    5 => $grandTotalStyle,
                    6 => $grandTotalStyle,
                    7 => $grandTotalStyle,
                    8 => $grandTotalSuccessStyle,
                    9 => $grandTotalDangerStyle,
                ],
            ));

            // --- Set Column Widths ---
            $writer->getCurrentSheet()->setColumnWidthForRange(18, 1, 1);  // Branch
            $writer->getCurrentSheet()->setColumnWidthForRange(16, 2, 2);  // Academic Year
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 3, 3);  // Term
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 4, 4);  // Total Learners
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 5, 5);  // Class
            $writer->getCurrentSheet()->setColumnWidthForRange(10, 6, 6);  // Boys
            $writer->getCurrentSheet()->setColumnWidthForRange(10, 7, 7);  // Girls
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 8, 8);  // Class Total
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 9, 9);  // Admitted
            $writer->getCurrentSheet()->setColumnWidthForRange(10, 10, 10); // Left
            $writer->getCurrentSheet()->setColumnWidthForRange(30, 11, 11); // Comment

            $writer->close();
        }, 'enrollment-report.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
