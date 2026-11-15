<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportCompilation as ModelReportCompilation;
use App\Models\ReportCompilationSource;
use App\Models\ReportSectionResponse;
use App\Models\ReportTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class ReportCompiler
{
    public const KIND_PRINCIPAL = 'principal';
    public const KIND_HEAD_TEACHER = 'head_teacher';

    private const TEMPLATE_NAMES = [
        self::KIND_PRINCIPAL => ['Principal Consolidated Report', 'Principal'],
        self::KIND_HEAD_TEACHER => ['Head Teacher Consolidated Report', 'Head Teacher'],
    ];

    public function __construct(
        private readonly ReportRouting $routing,
        private readonly ReportDataSources $sources,
    ) {
    }

    /**
     * Expected vs actual submissions for a period, for the actor's recipient role(s).
     *
     * @return list<array{key: string, user: ?User, name: string, branch: ?string, role: string, status: string, report_id: ?int, submitted_at: ?string, is_missing: bool}>
     */
    public function trackingFor(User $actor, \Carbon\CarbonInterface $periodStart, \Carbon\CarbonInterface $periodEnd): array
    {
        $rows = [];
        $seen = [];

        foreach ($actor->roles as $role) {
            foreach ($this->routing->expectedSubmittersFor($role->id) as $expected) {
                $user = $expected['user'];
                $key = $user->id;

                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $report = $this->findSubmission($user, $periodStart, $periodEnd);
                $isMissing = $report === null;

                $rows[] = [
                    'key' => 'expected-' . $user->id,
                    'user' => $user,
                    'name' => $user->name,
                    'branch' => $expected['branch'] ?? $user->branch?->name ?? (string) (\App\Models\Branch::find($user->branch_id)?->name),
                    'role' => $expected['rule']->submitterRole->name,
                    'status' => $report?->status->label() ?? 'Pending',
                    'report_id' => $report?->id,
                    'submitted_at' => $report?->submitted_at?->format('d/m/Y H:i'),
                    'is_missing' => $isMissing,
                ];
            }
        }

        usort($rows, fn ($a, $b) => strcmp((string) $a['branch'], (string) $b['branch']) ?: strcmp($a['role'], $b['role']) ?: strcmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * Find the relevant submission for a user in a period: their own report for the period,
     * or (for Head Teacher / Principal) the consolidated report they produced.
     */
    public function findSubmission(User $user, \Carbon\CarbonInterface $periodStart, \Carbon\CarbonInterface $periodEnd): ?Report
    {
        return Report::query()
            ->whereDate('period_start', '<=', $periodEnd->toDateString())
            ->whereDate('period_end', '>=', $periodStart->toDateString())
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere(fn ($inner) => $inner->where('compiled_by_user_id', $user->id)->where('is_consolidated', true));
            })
            ->whereNotIn('status', [ReportStatus::Draft->value, ReportStatus::Archived->value])
            ->orderByDesc('submitted_at')
            ->first();
    }

    /**
     * Compile a consolidated draft from the submitted source reports.
     */
    public function compileDraft(User $actor, \Carbon\CarbonInterface $periodStart, \Carbon\CarbonInterface $periodEnd, string $kind, array $sourceReportIds, bool $includeMissing): Report
    {
        abort_unless($actor->can('reports.compile') || $actor->isSuperAdmin(), 403, 'You are not authorized to compile reports.');

        [$templateName, $templateRole] = self::TEMPLATE_NAMES[$kind] ?? abort(422, 'Unknown compilation kind.');

        $role = \Spatie\Permission\Models\Role::where('name', $templateRole)->firstOrFail();
        $template = ReportTemplate::query()->where('name', $templateName)->where('role_id', $role->id)->first()
            ?? ReportTemplate::query()->where('name', $templateName)->firstOrFail();

        $version = $template->currentVersion()->with('sections')->firstOrFail();

        $sources = Report::query()
            ->whereIn('id', $sourceReportIds)
            ->with(['responses', 'user', 'template', 'branch', 'role'])
            ->get();

        $missing = $this->missingSources($actor, $periodStart, $periodEnd);

        $report = \DB::transaction(function () use ($actor, $periodStart, $periodEnd, $kind, $template, $version, $sources, $missing, $includeMissing, $role): Report {
            $report = Report::create([
                'user_id' => $actor->id,
                'title' => $template->name . ' — ' . CarbonImmutable::parse($periodStart)->format('F Y'),
                'template_id' => $template->id,
                'template_version_id' => $version->id,
                'role_id' => $actor->roles()->first()?->id,
                'branch_id' => null,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'deadline' => null,
                'frequency' => $template->frequency?->value,
                'status' => ReportStatus::Draft,
                'current_revision' => 1,
                'is_consolidated' => true,
                'compilation_kind' => $kind,
                'compiled_by_user_id' => $actor->id,
                'summary' => null,
            ]);

            foreach ($version->sections as $section) {
                $content = $this->composeSectionContent($section->title, (bool) $section->is_auto, $section->data_source, $sources, $missing, $includeMissing, $actor);

                ReportSectionResponse::create([
                    'report_id' => $report->id,
                    'section_id' => $section->id,
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                    'is_mandatory' => $section->is_mandatory,
                    'data_source' => $section->data_source,
                    'content' => $content['content'],
                    'auto_payload' => array_merge($content['payload'], ['compiled_from' => $sources->pluck('id')->all()]),
                ]);
            }

            $compilation = ModelReportCompilation::create([
                'report_id' => $report->id,
                'kind' => $kind,
                'compiler_role_id' => $role->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'branch_id' => null,
            ]);

            foreach ($sources as $source) {
                ReportCompilationSource::create([
                    'compilation_id' => $compilation->id,
                    'source_report_id' => $source->id,
                    'branch_label' => $this->sourceBranchLabel($source),
                    'contribution' => $this->contributionFor($source),
                ]);
            }

            return $report;
        });

        return $report;
    }

    /**
     * Track expected submissions that were not provided for the period.
     *
     * @return list<array{branch: ?string, role: string, name: string}>
     */
    private function missingSources(User $actor, \Carbon\CarbonInterface $periodStart, \Carbon\CarbonInterface $periodEnd): array
    {
        $missing = [];

        foreach ($this->trackingFor($actor, $periodStart, $periodEnd) as $row) {
            if ($row['is_missing']) {
                $missing[] = ['branch' => $row['branch'], 'role' => $row['role'], 'name' => $row['name']];
            }
        }

        return $missing;
    }

    /**
     * @return array{content: string, payload: array<string, mixed>}
     */
    private function composeSectionContent(string $title, bool $isAuto, ?string $dataSource, $sources, array $missing, bool $includeMissing, User $actor): array
    {
        $normalizedTitle = $this->normalize($title);

        // Population sections always come from live enrollment data.
        if ($dataSource === 'enrollment.org_breakdown' || str_contains($normalizedTitle, 'population') || str_contains($normalizedTitle, 'learnernumbers')) {
            return $this->sources->resolve('enrollment.org_breakdown', new User());
        }

        // Syllabus sections aggregate live coverage across the organization.
        if ($isAuto && ($dataSource === 'syllabus.coverage' || str_contains($normalizedTitle, 'syllabus') || str_contains($normalizedTitle, 'academic'))) {
            $orgActor = $this->orgActor($actor);
            $agg = $this->sources->resolve('syllabus.coverage', $orgActor);
            $from = $this->contributionsFrom($sources, ['syllabus', 'mapping', 'academic']);
            $body = $agg['content'] . ($from !== '' ? "\n\n" . $from : '');

            return ['content' => $body, 'payload' => $agg['payload']];
        }

        // Attendance sections aggregate live attendance data.
        if ($isAuto && (str_contains($normalizedTitle, 'attendance') || $dataSource === 'attendance.branch_summary' || $dataSource === 'attendance.org_summary')) {
            $agg = $this->sources->resolve('attendance.org_summary', $this->orgActor($actor));
            $from = $this->contributionsFrom($sources, ['attendance']);
            $body = $agg['content'] . ($from !== '' ? "\n\n" . $from : '');

            return ['content' => $body, 'payload' => $agg['payload']];
        }

        if ($isAuto && (str_contains($normalizedTitle, 'discipline') || $dataSource === 'discipline.branch_summary' || $dataSource === 'discipline.org_summary')) {
            $agg = $this->sources->resolve('discipline.org_summary', $this->orgActor($actor));
            $from = $this->contributionsFrom($sources, ['discipline']);
            $body = $agg['content'] . ($from !== '' ? "\n\n" . $from : '');

            return ['content' => $body, 'payload' => $agg['payload']];
        }

        // Everything else: concatenate matching narrative sections from source reports.
        $keywords = $this->sectionKeywords($normalizedTitle);
        $parts = [];

        foreach ($sources as $source) {
            $branch = $this->sourceBranchLabel($source);
            $matching = $source->responses->filter(function (ReportSectionResponse $response) use ($keywords): bool {
                $title = $this->normalize($response->title);
                foreach ($keywords as $keyword) {
                    if (str_contains($title, $keyword)) {
                        return true;
                    }
                }

                return false;
            });

            foreach ($matching as $response) {
                $content = trim((string) $response->content);
                if ($content !== '' && ! str_starts_with($content, 'No automatic data source')) {
                    $parts[] = "**{$branch}** — {$response->title}:\n" . $content;
                }
            }
        }

        $body = $parts === [] ? '' : implode("\n\n", $parts);

        if ($includeMissing && $missing !== []) {
            $flags = implode("\n", array_map(
                fn ($m) => '⚠️ MISSING SUBMISSION — ' . ($m['branch'] ?? 'All branches') . ' (' . $m['role'] . ($m['name'] ? ' · ' . $m['name'] : '') . ')',
                $missing
            ));
            $body = trim($body . "\n\n" . $flags);
        }

        if (trim($body) === '') {
            $body = 'No input was provided for this section.';
        }

        return ['content' => $body, 'payload' => ['available' => $parts !== [], 'sources' => $sources->pluck('id')->all()]];
    }

    private function contributionsFrom($sources, array $keywords): string
    {
        $parts = [];

        foreach ($sources as $source) {
            $branch = $this->sourceBranchLabel($source);
            $matching = $source->responses->filter(function (ReportSectionResponse $response) use ($keywords): bool {
                $title = $this->normalize($response->title);
                foreach ($keywords as $keyword) {
                    if (str_contains($title, $keyword)) {
                        return true;
                    }
                }

                return false;
            });

            foreach ($matching as $response) {
                $content = trim((string) $response->content);
                if ($content !== '') {
                    $parts[] = "**{$branch}** — {$content}";
                }
            }
        }

        return implode("\n\n", $parts);
    }

    private function orgActor(User $actor): User
    {
        // Resolve organization-wide aggregates through an unrestricted actor.
        return \App\Models\User::query()->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first() ?? $actor;
    }

    private function sourceBranchLabel(Report $source): string
    {
        return $source->branch?->name
            ?? \App\Models\Branch::find($source->branch_id)?->name
            ?? ($source->is_consolidated ? 'Consolidated report' : 'School-wide');
    }

    /** @return list<string> */
    private function sectionKeywords(string $normalizedTitle): array
    {
        $map = [
            'population' => ['population', 'learnernumbers', 'enrollment'],
            'learner' => ['population', 'learnernumbers', 'enrollment', 'learner'],
            'academic' => ['academic', 'position', 'syllabus', 'mapping'],
            'position' => ['position', 'academic', 'syllabus', 'mapping'],
            'achievement' => ['achievement', 'result', 'assessment'],
            'staff' => ['staff', 'development', 'professional'],
            'priorit' => ['priorit', 'issues', 'action', 'recommend', 'followup', 'pending'],
            'resource' => ['resource'],
            'event' => ['event'],
            'conclusion' => ['conclusion', 'summary'],
            'syllabus' => ['syllabus', 'mapping'],
            'attendance' => ['attendance'],
            'assessment' => ['assessment', 'formative'],
            'discipline' => ['discipline'],
            'lesson' => ['lesson'],
            'professional' => ['professional', 'documents'],
        ];

        foreach ($map as $needle => $keywords) {
            if (str_contains($normalizedTitle, $needle)) {
                return $keywords;
            }
        }

        // Fallback: use individual words longer than 4 chars as loose keywords.
        return array_values(array_filter(
            preg_split('/[^a-z0-9]+/', $normalizedTitle) ?: [],
            fn ($word) => strlen($word) > 4 && ! in_array($word, ['report', 'section'], true)
        ));
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', $value) ?? '');
    }

    /** @param  array<string, list<string>>  $map */
    private function contributionFor(Report $source): array
    {
        return [
            'subject' => $source->subject,
            'title' => $source->title,
            'status' => $source->status?->value,
            'sections' => $source->responses->map(fn (ReportSectionResponse $r) => $r->title)->all(),
        ];
    }
}
