<?php

namespace App\Filament\Pages;

use App\Models\SyllabusMapping;
use App\Models\Term;
use App\Models\YearSession;
use App\Services\SyllabusCoverage;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SyllabusDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationGroup = 'Syllabus';

    protected static ?string $navigationLabel = 'Syllabus Dashboard';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.syllabus-dashboard';

    public ?int $yearSessionId = null;

    public ?int $termId = null;

    public ?string $branch = null;

    public bool $canViewAll = false;

    public function mount(): void
    {
        $this->yearSessionId = YearSession::active()?->id;
        $this->termId = Term::active()?->id;
        $this->canViewAll = Auth::user()?->hasSyllabusViewAllAccess() ?? false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user
            && $user->can('syllabus.dashboard.view')
            && ($user->hasSyllabusViewAllAccess() || $user->hasSyllabusCoordinatorScope() || $user->subjectAssignments()->exists());
    }

    public function getTitle(): string
    {
        return 'Syllabus Dashboard';
    }

    public function getHeading(): string
    {
        return 'Syllabus Coverage Dashboard';
    }

    public function getBreadcrumbs(): array
    {
        return ['Syllabus'];
    }

    public function getYearOptionsProperty(): array
    {
        return YearSession::query()->orderByDesc('id')->pluck('name', 'id')->all();
    }

    public function getTermOptionsProperty(): array
    {
        $query = Term::query()->orderBy('name');

        if ($this->yearSessionId) {
            $query->where('year_session_id', $this->yearSessionId);
        }

        return $query->pluck('name', 'id')->all();
    }

    public function getBranchOptionsProperty(): array
    {
        $user = Auth::user();

        if ($user && ! $user->hasUnrestrictedAccess()) {
            $names = $user->assignableBranchNames();

            return array_combine($names, $names);
        }

        return \App\Models\Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getMappingRowsProperty()
    {
        $user = Auth::user();
        $coverage = app(SyllabusCoverage::class);

        return SyllabusMapping::query()
            ->accessibleTo($user)
            ->with(['class.branch', 'subject', 'term', 'yearSession', 'stream'])
            ->when($this->yearSessionId, fn ($query) => $query->where('year_session_id', $this->yearSessionId))
            ->when($this->termId, fn ($query) => $query->where('term_id', $this->termId))
            ->when($this->branch, fn ($query) => $query->whereHas('class.branch', fn ($branchQuery) => $branchQuery->where('name', $this->branch)))
            ->get()
            ->map(function (SyllabusMapping $mapping) use ($coverage): array {
                $teacher = $mapping->assignedTeacher();
                $stats = $coverage->stats($mapping);

                return [
                    'mapping' => $mapping,
                    'grade' => $mapping->grade_stream,
                    'subject' => $mapping->subject->name,
                    'branch' => $mapping->branch,
                    'teacher' => $teacher?->name,
                    'teacher_id' => $teacher?->id,
                    'status' => $mapping->status->value,
                    'total_lessons' => $stats['total_lessons'],
                    'completed_lessons' => $stats['completed_lessons'],
                    'pending_lessons' => $stats['pending_lessons'],
                    'overdue_lessons' => $stats['overdue_lessons'],
                    'late_lessons' => $stats['late_lessons'],
                    'coverage_percent' => $stats['coverage_percent'],
                    'highest_consecutive' => $stats['highest_consecutive'],
                    'gaps_count' => count($stats['gaps']),
                ];
            })
            ->sortByDesc('coverage_percent')
            ->values();
    }

    public function getSummaryProperty(): array
    {
        $rows = $this->mappingRows;

        $active = $rows->where('status', 'active')->count();
        $subjects = $rows->pluck('subject')->unique()->count();
        $grades = $rows->where('status', 'active')->pluck('grade')->unique()->count();
        $overall = $rows->count() ? round($rows->avg('coverage_percent'), 2) : 0.0;
        $overdue = $rows->where('overdue_lessons', '>', 0)->count();

        return [
            'mappings' => $rows->count(),
            'active' => $active,
            'subjects' => $subjects,
            'grades' => $grades,
            'overall' => $overall,
            'overdue' => $overdue,
        ];
    }

    public function getTopCoverageProperty()
    {
        return $this->mappingRows->sortByDesc('coverage_percent')->take(5)->values();
    }

    public function getLowestCoverageProperty()
    {
        return $this->mappingRows->sortBy('coverage_percent')->take(5)->values();
    }

    public function getOverdueMappingsProperty()
    {
        return $this->mappingRows->filter(fn ($row) => $row['overdue_lessons'] > 0)->sortByDesc('overdue_lessons')->values();
    }

    /**
     * Teachers with zero completed lessons, paired with the subject(s) they have not updated.
     *
     * @return \Illuminate\Support\Collection<int, array{teacher: string, subjects: array<int, string>}>
     */
    public function getTeachersNotUpdatedProperty()
    {
        return $this->mappingRows
            ->filter(fn ($row) => $row['completed_lessons'] === 0 && $row['total_lessons'] > 0 && filled($row['teacher']))
            ->groupBy('teacher')
            ->map(function ($group, $teacher): array {
                $subjects = collect($group)
                    ->pluck('subject')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return ['teacher' => $teacher, 'subjects' => $subjects];
            })
            ->sortBy('teacher')
            ->values();
    }

    public function getCoverageByCampusProperty()
    {
        return $this->mappingRows
            ->groupBy(fn ($row) => $row['branch'] ?? 'School-wide')
            ->map(fn ($group, $branch) => [
                'branch' => $branch,
                'mappings' => $group->count(),
                'coverage' => round($group->avg('coverage_percent'), 2),
            ])
            ->values();
    }

    public function getGradeBreakdownProperty()
    {
        return $this->mappingRows
            ->groupBy('grade')
            ->map(fn ($group, $grade) => [
                'grade' => $grade,
                'subjects' => $group->pluck('subject')->unique()->count(),
                'coverage' => round($group->avg('coverage_percent'), 2),
            ])
            ->sortBy('grade')
            ->values();
    }

    public function getReportUrlProperty(): string
    {
        return route('syllabus.reports.coverage.pdf', array_filter([
            'year_session_id' => $this->yearSessionId,
            'term_id' => $this->termId,
            'branch' => $this->branch,
        ]));
    }

    public function getReportExcelUrlProperty(): string
    {
        return route('syllabus.reports.coverage.excel', array_filter([
            'year_session_id' => $this->yearSessionId,
            'term_id' => $this->termId,
            'branch' => $this->branch,
        ]));
    }
}
