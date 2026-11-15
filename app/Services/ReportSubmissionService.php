<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportRevision;
use App\Models\ReportReview;
use App\Models\ReportSectionResponse;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Notifications\ReportWorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ReportSubmissionService
{
    public function __construct(private readonly ReportRouting $routing)
    {
    }

    /**
     * Create a draft report from the current version of a template for the given user.
     */
    public function createFromTemplate(User $user, ReportTemplate $template, ?string $periodStart = null, ?string $periodEnd = null, ?string $deadline = null): Report
    {
        $version = $template->currentVersion;
        abort_if(! $version, 422, 'This template has no published version yet.');

        return DB::transaction(function () use ($user, $template, $version, $periodStart, $periodEnd, $deadline): Report {
            $role = $user->roles()->whereIn('name', $template->role?->name ? [$template->role->name] : [])->first() ?? $user->roles()->first();
            $period = $template->frequency?->periodFor(now()) ?? [now()->startOfMonth(), now()->endOfMonth()];

            $report = Report::create([
                'user_id' => $user->id,
                'title' => $template->name . ' — ' . now()->format('F Y'),
                'template_id' => $template->id,
                'template_version_id' => $version->id,
                'role_id' => $role?->id,
                'branch_id' => $user->branch_id,
                'period_start' => $periodStart ?? $period[0]->toDateString(),
                'period_end' => $periodEnd ?? $period[1]->toDateString(),
                'deadline' => $deadline,
                'frequency' => $template->frequency?->value,
                'status' => ReportStatus::Draft,
                'current_revision' => 1,
                'summary' => null,
            ]);

            foreach ($version->sections as $section) {
                $auto = $section->is_auto && $section->data_source
                    ? app(ReportDataSources::class)->resolve($section->data_source, $user)
                    : ['content' => '', 'payload' => ['available' => false]];

                ReportSectionResponse::create([
                    'report_id' => $report->id,
                    'section_id' => $section->id,
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                    'is_mandatory' => $section->is_mandatory,
                    'data_source' => $section->data_source,
                    'content' => $auto['content'] ?: null,
                    'auto_payload' => $auto['payload'],
                ]);
            }

            return $report;
        });
    }

    /**
     * Submit a draft (or resubmit a returned report).
     */
    public function submit(Report $report, User $actor, ?string $note = null): Report
    {
        abort_unless($this->canSubmit($report, $actor), 403, 'You are not authorized to submit this report.');

        $this->validateMandatory($report);

        $isResubmission = in_array($report->status, [ReportStatus::Returned, ReportStatus::Resubmitted], true);

        DB::transaction(function () use ($report, $actor, $note, $isResubmission): void {
            $report->load('responses', 'revisions');

            $revisionNumber = $report->revisions()->max('revision_number') + 1;

            $report->forceFill([
                'status' => $isResubmission ? ReportStatus::Resubmitted : ReportStatus::Submitted,
                'submitted_at' => now(),
                'current_revision' => $revisionNumber,
            ])->save();

            $this->snapshotRevision($report, $note, $actor->id, $revisionNumber);
            $this->recordReview($report, $actor, $isResubmission ? 'resubmitted' : 'submitted', $note);
            $this->snapshotRecipients($report, $actor);
            $this->notifyRecipients($report, $actor, $isResubmission ? 'resubmitted' : 'submitted');
        });

        return $report->refresh();
    }

    /**
     * Recipient action: mark received.
     */
    public function markReceived(Report $report, User $actor): Report
    {
        $this->authorizeReviewer($report, $actor, 'reports.review');

        DB::transaction(function () use ($report, $actor): void {
            $report->forceFill(['status' => ReportStatus::Received, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();
            $this->recordReview($report, $actor, 'received', null);
            $this->notifyUser($report->user, $report, $actor, 'received');
        });

        return $report->refresh();
    }

    /**
     * Recipient action: mark under review.
     */
    public function markUnderReview(Report $report, User $actor, ?string $comment = null): Report
    {
        $this->authorizeReviewer($report, $actor, 'reports.review');

        DB::transaction(function () use ($report, $actor, $comment): void {
            $report->forceFill(['status' => ReportStatus::UnderReview, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();
            $this->recordReview($report, $actor, 'under_review', $comment);
            $this->notifyUser($report->user, $report, $actor, 'under_review', $comment);
        });

        return $report->refresh();
    }

    /**
     * Recipient action: return for revision with a mandatory comment.
     */
    public function returnForRevision(Report $report, User $actor, string $comment): Report
    {
        if (trim($comment) === '') {
            throw ValidationException::withMessages(['comment' => 'A comment is required when returning a report for revision.']);
        }

        $this->authorizeReviewer($report, $actor, 'reports.return');

        DB::transaction(function () use ($report, $actor, $comment): void {
            $report->forceFill(['status' => ReportStatus::Returned, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();
            $this->recordReview($report, $actor, 'returned', $comment);
            $this->notifyUser($report->user, $report, $actor, 'returned', $comment);
        });

        return $report->refresh();
    }

    /**
     * Recipient action: approve.
     */
    public function approve(Report $report, User $actor, ?string $comment = null): Report
    {
        $this->authorizeReviewer($report, $actor, 'reports.approve');

        DB::transaction(function () use ($report, $actor, $comment): void {
            $report->forceFill(['status' => ReportStatus::Approved, 'reviewed_by' => $actor->id, 'reviewed_at' => now()])->save();
            $this->recordReview($report, $actor, 'approved', $comment);
            $this->notifyUser($report->user, $report, $actor, 'approved', $comment);
        });

        return $report->refresh();
    }

    public function archive(Report $report, User $actor): Report
    {
        $this->authorizeReviewer($report, $actor, 'reports.review');

        DB::transaction(function () use ($report, $actor): void {
            $report->forceFill(['status' => ReportStatus::Archived])->save();
            $this->recordReview($report, $actor, 'archived', null);
        });

        return $report->refresh();
    }

    /**
     * Refresh the auto-populated sections from current system data.
     */
    public function refreshAutoSections(Report $report): Report
    {
        $actor = $report->user;
        abort_unless($actor, 422, 'This report has no submitter.');

        foreach ($report->responses()->whereNotNull('data_source')->get() as $response) {
            if (! $response->data_source) {
                continue;
            }

            $resolved = app(ReportDataSources::class)->resolve($response->data_source, $actor);
            $response->forceFill(['content' => $resolved['content'], 'auto_payload' => $resolved['payload']])->save();
        }

        return $report->refresh();
    }

    public function canSubmit(Report $report, User $actor): bool
    {
        return $report->user_id === $actor->id
            && in_array($report->status, [ReportStatus::Draft, ReportStatus::Returned], true);
    }

    /**
     * Strip scripts/handlers from user-authored (and our own) HTML before it is stored.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Remove dangerous elements entirely.
        $html = preg_replace('#<(script|style|iframe|object|embed|form|input|button|select|textarea)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|style|iframe|object|embed|form|input|button|select|textarea)[^>]*/?>#is', '', $html) ?? '';
        // Remove on* event handler attributes.
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';
        // Neutralize javascript: URLs.
        $html = preg_replace('#(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')#i', '$1=$2#$3', $html) ?? '';

        return $html;
    }

    private function authorizeReviewer(Report $report, User $actor, string $permission): void
    {
        $isRecipient = $report->recipients()->where('users.id', $actor->id)->exists();

        abort_unless(
            $actor->can($permission) && ($isRecipient || $actor->can('reports.view_all') || $actor->isSuperAdmin()),
            403,
            'You are not authorized to review this report.'
        );
    }

    private function validateMandatory(Report $report): void
    {
        $missing = $report->responses()
            ->where('is_mandatory', true)
            ->get()
            ->filter(fn (ReportSectionResponse $response) => trim((string) $response->content) === '')
            ->pluck('title');

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'sections' => 'Please complete the mandatory section(s): ' . $missing->implode(', '),
            ]);
        }
    }

    private function snapshotRevision(Report $report, ?string $note, int $actorId, int $revisionNumber): void
    {
        ReportRevision::create([
            'report_id' => $report->id,
            'revision_number' => $revisionNumber,
            'snapshot' => [
                'title' => $report->title,
                'summary' => $report->summary,
                'branch_id' => $report->branch_id,
                'academic_level_id' => $report->academic_level_id,
                'period_start' => $report->period_start?->toDateString(),
                'period_end' => $report->period_end?->toDateString(),
                'responses' => $report->responses->map(fn (ReportSectionResponse $r) => [
                    'title' => $r->title,
                    'sort_order' => $r->sort_order,
                    'is_mandatory' => $r->is_mandatory,
                    'data_source' => $r->data_source,
                    'content' => $r->content,
                ])->all(),
            ],
            'note' => $note,
            'created_by' => $actorId,
            'created_at' => now(),
        ]);
    }

    private function recordReview(Report $report, User $actor, string $action, ?string $comment): void
    {
        ReportReview::create([
            'report_id' => $report->id,
            'user_id' => $actor->id,
            'action' => $action,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    private function snapshotRecipients(Report $report, User $actor): void
    {
        $recipientIds = $this->routing->recipientsFor($actor)->pluck('id');

        $report->recipients()->syncWithoutDetaching($recipientIds->all());
    }

    private function notifyRecipients(Report $report, User $actor, string $action): void
    {
        $recipients = $report->recipients;

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ReportWorkflowNotification($report, $actor, $action));
    }

    private function notifyUser(User $recipient, Report $report, User $actor, string $action, ?string $comment = null): void
    {
        $recipient->notify(new ReportWorkflowNotification($report, $actor, $action, $comment));
    }
}
