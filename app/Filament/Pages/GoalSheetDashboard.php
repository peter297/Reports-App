<?php

namespace App\Filament\Pages;

use App\Models\GoalSheet;
use App\Models\GoalSheetCategory;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class GoalSheetDashboard extends Page
{
    protected static string $view = 'filament.pages.goal-sheet-dashboard';

    protected static ?string $navigationGroup = 'Goal Sheets';

    protected static ?string $navigationLabel = 'Goal Sheet Dashboard';

    protected static ?string $title = 'Goal Sheet Dashboard';

    protected static ?int $navigationSort = 1;

    public string $goalYear = '';

    public string $categoryId = '';

    public string $coordinator = '';

    public string $department = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('goalsheets.reports'), 403);
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('goalsheets.reports') ?? false;
    }

    /** Base query: permission + row scope applied. */
    protected function scopedSheets(): Builder
    {
        $user = Auth::user();

        $query = GoalSheet::query()
            ->with(['category', 'staff', 'coordinator', 'acknowledgements', 'goals.entries'])
            ->when($this->goalYear !== '', fn (Builder $q) => $q->where('goal_year', (int) $this->goalYear))
            ->when($this->categoryId !== '', fn (Builder $q) => $q->where('goal_sheet_category_id', (int) $this->categoryId))
            ->when($this->coordinator !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('coordinator_name', $this->coordinator)->orWhereHas('coordinator', fn (Builder $c) => $c->where('name', $this->coordinator))))
            ->when($this->department !== '', fn (Builder $q) => $q->where('department', $this->department));

        return $user ? $query->accessibleTo($user) : $query->whereKey(0);
    }

    public function getStatsProperty(): array
    {
        return $this->remember('stats', 30, function () {
            $sheets = $this->scopedSheets()->get();

            $rated = $sheets->map(fn (GoalSheet $sheet) => $sheet->calculatedWeightedRating())->filter()->values();

            return [
                'total' => $sheets->count(),
                'draft' => $sheets->where('status', GoalSheet::STATUS_DRAFT)->count(),
                'submitted' => $sheets->where('status', GoalSheet::STATUS_SUBMITTED)->count(),
                'acknowledged' => $sheets->where('status', GoalSheet::STATUS_ACKNOWLEDGED)->count(),
                'pending_ack' => $sheets->filter(fn (GoalSheet $sheet) => $sheet->pendingAcknowledgement())->count(),
                'avg_rating' => $rated->isEmpty() ? null : round($rated->avg(), 1),
            ];
        });
    }

    public function getByCategoryProperty(): Collection
    {
        return $this->remember('by_category', 30, function () {
            return $this->scopedSheets()->get()
                ->groupBy('goal_sheet_category_id')
                ->map(function (Collection $group) {
                    $rated = $group->map(fn (GoalSheet $s) => $s->calculatedWeightedRating())->filter()->values();

                    return [
                        'name' => $group->first()->category?->name ?? 'Unknown',
                        'sheets' => $group->count(),
                        'acknowledged' => $group->where('status', GoalSheet::STATUS_ACKNOWLEDGED)->count(),
                        'pending' => $group->filter(fn (GoalSheet $s) => $s->pendingAcknowledgement())->count(),
                        'avg_rating' => $rated->isEmpty() ? null : round($rated->avg(), 1),
                    ];
                })
                ->sortByDesc('sheets')
                ->values();
        });
    }

    public function getByCoordinatorProperty(): Collection
    {
        return $this->remember('by_coordinator', 30, function () {
            return $this->scopedSheets()->get()
                ->groupBy(fn (GoalSheet $sheet) => $this->groupKey((string) (($sheet->coordinator?->name ?? $sheet->coordinator_name) ?: 'Unassigned')))
                ->map(function (Collection $group) {
                    $rated = $group->map(fn (GoalSheet $s) => $s->calculatedWeightedRating())->filter()->values();
                    $name = $this->displayName($group, fn (GoalSheet $s) => (string) (($s->coordinator?->name ?? $s->coordinator_name) ?: 'Unassigned'));

                    return [
                        'name' => $name,
                        'sheets' => $group->count(),
                        'acknowledged' => $group->where('status', GoalSheet::STATUS_ACKNOWLEDGED)->count(),
                        'avg_rating' => $rated->isEmpty() ? null : round($rated->avg(), 1),
                    ];
                })
                ->sortByDesc('sheets')
                ->values();
        });
    }

    public function getByDepartmentProperty(): Collection
    {
        return $this->remember('by_department', 30, function () {
            return $this->scopedSheets()->get()
                ->groupBy(fn (GoalSheet $sheet) => $this->groupKey((string) $sheet->department ?: 'Unspecified'))
                ->map(function (Collection $group) {
                    $rated = $group->map(fn (GoalSheet $s) => $s->calculatedWeightedRating())->filter()->values();
                    $name = $this->displayName($group, fn (GoalSheet $s) => (string) $s->department ?: 'Unspecified');

                    return [
                        'name' => $name,
                        'sheets' => $group->count(),
                        'acknowledged' => $group->where('status', GoalSheet::STATUS_ACKNOWLEDGED)->count(),
                        'avg_rating' => $rated->isEmpty() ? null : round($rated->avg(), 1),
                    ];
                })
                ->sortByDesc('sheets')
                ->values();
        });
    }

    /** Normalized grouping key: trim, collapse spaces and non-breaking spaces, lowercase. */
    protected function groupKey(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $name)) ?? $name));
    }

    /** Most common trimmed original spelling in the group (displayed as stored). */
    protected function displayName(Collection $group, callable $nameFor): string
    {
        $counts = $group->map($nameFor)
            ->map(fn (string $name) => trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $name)) ?? $name))
            ->countBy()
            ->sortDesc();

        return $counts->keys()->first() ?? 'Unspecified';
    }

    /** Individual sheets for the detail table (authorized users can drill in). */
    public function getSheetRowsProperty(): Collection
    {
        return $this->remember('rows', 30, function () {
            return $this->scopedSheets()
                ->orderByDesc('submitted_at')
                ->orderBy('staff_name')
                ->limit(100)
                ->get()
                ->map(fn (GoalSheet $sheet) => [
                    'id' => $sheet->id,
                    'staff' => $sheet->staff_name,
                    'category' => $sheet->category?->name,
                    'department' => $sheet->department ?: '—',
                    'coordinator' => $sheet->coordinator?->name ?? $sheet->coordinator_name ?? '—',
                    'status' => GoalSheet::STATUS_LABELS[$sheet->status] ?? $sheet->status,
                    'status_key' => $sheet->status,
                    'rating' => $sheet->calculatedWeightedRating(),
                    'pending_ack' => $sheet->pendingAcknowledgement(),
                    'url' => \App\Filament\Resources\GoalSheetResource::getUrl('view', ['record' => $sheet->id]),
                ]);
        });
    }

    protected function remember(string $key, int $ttl, callable $callback): mixed
    {
        $signature = md5(json_encode([Auth::id(), $this->goalYear, $this->categoryId, $this->coordinator, $this->department, $key]));

        return Cache::remember('goalsheets.dashboard.' . $signature, $ttl, $callback);
    }

    public function updated(string $property, mixed $value): void
    {
        // Filters are read from view repeatedly; no extra work needed here.
    }

    public function getYearOptionsProperty(): array
    {
        return GoalSheet::query()->distinct()->orderByDesc('goal_year')->pluck('goal_year', 'goal_year')->all();
    }

    public function getCategoryOptionsProperty(): array
    {
        return GoalSheetCategory::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->all();
    }

    public function getCoordinatorOptionsProperty(): array
    {
        $names = GoalSheet::query()->whereNotNull('coordinator_name')->distinct()->orderBy('coordinator_name')->pluck('coordinator_name');
        $userNames = GoalSheet::query()->whereNotNull('coordinator_id')->with('coordinator:id,name')->get()->pluck('coordinator.name')->filter()->unique();

        return $names->merge($userNames)->filter()->unique()->sort()->values()->mapWithKeys(fn ($name) => [$name => $name])->all();
    }

    public function getDepartmentOptionsProperty(): array
    {
        return GoalSheet::query()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department', 'department')->all();
    }

    public function getStatsViewProperty(): array
    {
        return $this->getStatsProperty();
    }
}
