<?php

namespace App\Filament\Resources\AcademicAnalysisResource\Widgets;

use App\Models\AcademicAnalysis;
use App\Models\Term;
use App\Models\YearSession;
use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Facades\Auth;

class BranchComparisonChart extends BarChartWidget
{
    protected static ?string $heading = 'Branch Comparison by Section';

    public static function canView(): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess() || $user?->isHead() || $user?->isDeputy()) ?? false;
    }

    protected function getFilters(): ?array
    {
        $year = YearSession::active();

        if (! $year) {
            return null;
        }

        return Term::query()
            ->where('year_session_id', $year->id)
            ->orderBy('id')
            ->pluck('name', 'id')
            ->all() ?: null;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $year = YearSession::active();
        $termId = $this->filter ? (int) $this->filter : Term::active()?->id;

        if (! $user || ! $year || ! $termId) {
            return ['datasets' => [], 'labels' => []];
        }

        $branches = ['Juja Road', 'Kitisuru', 'South C'];

        if ($user->isDeputy() && ! $user->hasUnrestrictedAccess() && ! $user->isHead()) {
            $branches = $user->branch ? [$user->branch] : [];
        }

        $sections = ['EYE', 'Upper Primary', 'Junior School'];
        $colors = ['#f59e0b', '#3b82f6', '#10b981'];

        $datasets = [];

        foreach ($branches as $i => $branch) {
            $points = [];

            foreach ($sections as $section) {
                $points[] = AcademicAnalysis::meanOfAverages(
                    AcademicAnalysis::query()
                        ->accessibleTo($user)
                        ->forTerm($year->id, $termId)
                        ->where('branch', $branch)
                        ->where('section', $section)
                        ->get()
                );
            }

            $datasets[] = [
                'label' => $branch,
                'data' => $points,
                'backgroundColor' => $colors[$i % count($colors)],
            ];
        }

        return ['datasets' => $datasets, 'labels' => $sections];
    }
}
