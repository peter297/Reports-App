<?php

namespace App\Filament\Resources\AcademicAnalysisResource\Widgets;

use App\Models\AcademicAnalysis;
use App\Models\Term;
use App\Models\YearSession;
use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Facades\Auth;

class SubjectAveragesChart extends BarChartWidget
{
    protected static ?string $heading = 'Subject Averages by Section';

    protected function getFilters(): ?array
    {
        return ['Mid Term' => 'Mid Term', 'End Term' => 'End Term'];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $exam = $this->filter ?? 'Mid Term';
        $year = YearSession::active();
        $term = Term::active();

        if (! $user || ! $year || ! $term) {
            return ['datasets' => [], 'labels' => []];
        }

        $sections = $this->sectionsInScope($user);

        $base = AcademicAnalysis::query()
            ->accessibleTo($user)
            ->forTerm($year->id, $term->id, $exam)
            ->get();

        $subjects = $base->flatMap(fn (AcademicAnalysis $a): array => collect($a->subjectStats())->pluck('subject')->all())
            ->unique()
            ->values()
            ->all();

        $colors = ['#1f2937', '#059669', '#2563eb', '#b45309'];

        $datasets = [];

        foreach ($sections as $i => $section) {
            $means = AcademicAnalysis::compiledSubjectMeans(
                $base->where('section', $section)->values()
            );

            $bySubject = collect($means)->pluck('average', 'subject');

            $datasets[] = [
                'label' => $section,
                'data' => array_map(fn (string $subject): float => $bySubject->get($subject, 0), $subjects),
                'backgroundColor' => $colors[$i % count($colors)],
            ];
        }

        return ['datasets' => $datasets, 'labels' => $subjects];
    }

    /**
     * @return array<int, string>
     */
    protected function sectionsInScope(\App\Models\User $user): array
    {
        if ($user->hasUnrestrictedAccess() || $user->isHead() || $user->isDeputy()) {
            return ['EYE', 'Upper Primary', 'Junior School'];
        }

        $sections = $user->sectionCoordinatorAssignments()->distinct()->pluck('section')->all();

        return array_values(array_intersect(['EYE', 'Upper Primary', 'Junior School'], $sections));
    }
}
