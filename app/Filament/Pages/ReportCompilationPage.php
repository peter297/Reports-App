<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrganizationalReportResource;
use App\Models\Report;
use App\Services\ReportCompiler;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ReportCompilationPage extends Page
{
    protected static ?string $slug = 'report-compilation';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('reports.compile') || $user->isSuperAdmin());
    }

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationGroup = 'Overview';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Report Compilation';

    protected static string $view = 'filament.pages.report-compilation';

    public ?string $kind = null;

    public string $periodStart;

    public string $periodEnd;

    /** @var array<int, int> selected source report ids */
    public array $selected = [];

    public bool $includeMissing = false;

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user && ($user->can('reports.compile') || $user->isSuperAdmin()), 403);

        $now = now();
        $this->periodStart = $now->copy()->startOfMonth()->toDateString();
        $this->periodEnd = $now->copy()->endOfMonth()->toDateString();
        $this->kind = $this->availableKinds()[array_key_first($this->availableKinds())] ?? ReportCompiler::KIND_PRINCIPAL;
    }

    public function getTitle(): string
    {
        return 'Report Compilation';
    }

    public function getBreadcrumbs(): array
    {
        return ['Reports', 'Report Compilation'];
    }

    /** @return array<string, string> */
    public function availableKinds(): array
    {
        $user = Auth::user();

        $kinds = [];

        if ($user->hasRole('Principal') || $user->isSuperAdmin()) {
            $kinds['Principal Consolidated Report (from branch Administrators, ICT and HR)'] = ReportCompiler::KIND_PRINCIPAL;
        }

        if ($user->hasRole('Head Teacher') || $user->isSuperAdmin()) {
            $kinds['Head Teacher Consolidated Report (from Coordinators, DHT and Deans)'] = ReportCompiler::KIND_HEAD_TEACHER;
        }

        return $kinds;
    }

    public function getKindProperty(): ?string
    {
        return $this->kind;
    }

    public function getTrackingRowsProperty(): array
    {
        $user = Auth::user();

        if (! $this->kind) {
            return [];
        }

        $rows = app(ReportCompiler::class)->trackingFor($user, Carbon::parse($this->periodStart), Carbon::parse($this->periodEnd));

        // Pre-select submitted sources relevant to the chosen compilation kind.
        $defaultKind = $this->kind === ReportCompiler::KIND_PRINCIPAL ? ['Administrator', 'ICT Officer', 'HR Officer', 'Head Teacher', 'Deputy Principal'] : ['Coordinator', 'Deputy Head Teacher', 'Dean of Islamic Studies'];

        return array_map(function (array $row) {
            $report = null;

            if ($row['report_id']) {
                $report = Report::with(['responses', 'user', 'branch', 'role', 'template'])->find($row['report_id']);
            }

            $row['report'] = $report;
            $row['selected'] = (bool) $report;
            $row['overdue'] = $report === null && Carbon::parse($this->periodEnd)->isPast();

            return $row;
        }, $rows);
    }

    public function getCompilationRowsProperty(): array
    {
        return Report::query()
            ->where('is_consolidated', true)
            ->where('compiled_by_user_id', Auth::id())
            ->whereDate('period_start', '<=', Carbon::parse($this->periodEnd)->toDateString())
            ->whereDate('period_end', '>=', Carbon::parse($this->periodStart)->toDateString())
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Report $report): array => [
                'id' => $report->id,
                'title' => $report->title,
                'kind' => str_replace('_', ' ', (string) $report->compilation_kind),
                'status' => $report->status->label(),
                'submitted_at' => $report->submitted_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    public function toggleAllSubmitted(bool $value): void
    {
        $this->selected = array_values(array_filter(array_column(
            array_filter($this->getTrackingRowsProperty(), fn ($row) => $row['report_id'] !== null && $row['report_id']),
            'report_id'
        ), fn ($id) => $id !== null));

        if (! $value) {
            $this->selected = [];
        }
    }

    public function compile(): void
    {
        $user = Auth::user();

        $report = app(ReportCompiler::class)->compileDraft(
            $user,
            Carbon::parse($this->periodStart),
            Carbon::parse($this->periodEnd),
            $this->kind,
            array_map('intval', $this->selected),
            (bool) $this->includeMissing,
        );

        Notification::make()
            ->title('Draft consolidated report created')
            ->body('Review and edit it, then submit it to the next recipient.')
            ->success()
            ->send();

        $this->redirect(OrganizationalReportResource::getUrl('edit', ['record' => $report->id]));
    }
}
