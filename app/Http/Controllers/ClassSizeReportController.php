<?php

namespace App\Http\Controllers;

use App\Models\ClassSize;
use App\Models\Term;
use App\Models\YearSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ClassSizeReportController extends Controller
{
    public function pdf(int $yearSession, int $term): Response
    {
        $session = YearSession::findOrFail($yearSession);
        $termModel = Term::findOrFail($term);

        $user = auth()->user();
        abort_unless(
            $user?->hasUnrestrictedAccess()
            || $user?->can('view_any_class::size')
            || $user?->hasRole('Coordinators'),
            403
        );

        $sizes = ClassSize::query()
            ->accessibleTo($user)
            ->where('year_session_id', $session->id)
            ->where('term_id', $termModel->id)
            ->with(['class', 'stream'])
            ->orderBy('branch')
            ->orderBy('section')
            ->get();

        $sections = [];
        $grandBoys = 0;
        $grandGirls = 0;
        $grandTotal = 0;

        foreach ($sizes->groupBy('section') as $sectionName => $rows) {
            $boys = (int) $rows->sum('total_boys');
            $girls = (int) $rows->sum('total_girls');
            $total = (int) $rows->sum('class_total');

            $grandBoys += $boys;
            $grandGirls += $girls;
            $grandTotal += $total;

            $sections[] = [
                'label' => $sectionName,
                'rows' => $rows->sortBy([
                    fn ($a, $b): int => strcmp((string) $a->branch, (string) $b->branch)
                        ?: strcmp((string) $a->class?->name, (string) $b->class?->name)
                        ?: strcmp((string) $a->stream?->name, (string) $b->stream?->name),
                ])->values(),
                'boys' => $boys,
                'girls' => $girls,
                'total' => $total,
            ];
        }

        usort($sections, fn (array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));

        return Pdf::loadView('pdf.class-sizes', [
            'session' => $session,
            'term' => $termModel,
            'sections' => $sections,
            'grandBoys' => $grandBoys,
            'grandGirls' => $grandGirls,
            'grandTotal' => $grandTotal,
            'rowCount' => $sizes->count(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream("class-sizes-{$session->id}-{$termModel->id}-report.pdf");
    }
}
