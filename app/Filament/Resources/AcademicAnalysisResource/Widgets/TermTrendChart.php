<?php

namespace App\Filament\Resources\AcademicAnalysisResource\Widgets;

use App\Models\AcademicAnalysis;
use App\Models\Term;
use App\Models\User;
use App\Models\YearSession;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\Auth;

class TermTrendChart extends LineChartWidget
{
    protected static ?string $heading = 'Section Trend Across Terms';

    protected function getFilters(): ?array
    {
        return ['Mid Term' => 'Mid Term', 'End Term' => 'End Term'];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $exam = $this->filter ?? 'Mid Term';
        $year = YearSession::active();

        if (! $user || ! $year) {
            return ['datasets' => [], 'labels' => []];
        }

        $terms = Term::query()->where('year_session_id', $year->id)->orderBy('id')->get();
        $sections = $this->sectionsInScope($user);
        $colors = ['#1f2937', '#059669', '#2563eb', '#b45309'];

        $datasets = [];

        foreach ($sections as $i => $section) {
            $points = [];

            foreach ($terms as $term) {
                $points[] = AcademicAnalysis::meanOfAverages(
                    AcademicAnalysis::query()
                        ->accessibleTo($user)
                        ->forTerm($year->id, $term->id, $exam)
                        ->where('section', $section)
                        ->get()
                );
            }

            $datasets[] = [
                'label' => $section,
                'data' => $points,
                'borderColor' => $colors[$i % count($colors)],
                'backgroundColor' => $colors[$i % count($colors)],
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $terms->pluck('name')->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sectionsInScope(User $user): array
    {
        if ($user->hasUnrestrictedAccess() || $user->isHead() || $user->isDeputy()) {
            return ['EYE', 'Upper Primary', 'Junior School'];
        }

        $sections = $user->sectionCoordinatorAssignments()->distinct()->pluck('section')->all();

        return array_values(array_intersect(['EYE', 'Upper Primary', 'Junior School'], $sections));
    }
}
