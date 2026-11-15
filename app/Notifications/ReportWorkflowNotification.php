<?php

namespace App\Notifications;

use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Report $report,
        public readonly User $actor,
        public readonly string $action,
        public readonly ?string $comment = null,
        public readonly ?string $urlOverride = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_workflow',
            'dedupe_key' => 'report:' . $this->report->id . ':' . $this->action . ':' . now()->format('Y-m-d'),
            'action' => $this->action,
            'title' => $this->headline(),
            'message' => $this->message(),
            'report_id' => $this->report->id,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => $this->urlOverride ?? \App\Filament\Resources\OrganizationalReportResource::getUrl('view', ['record' => $this->report->id]),
            'comment' => $this->comment,
        ];
    }

    private function headline(): string
    {
        return match ($this->action) {
            'submitted' => 'New report submitted',
            'resubmitted' => 'Report resubmitted',
            'received' => 'Report marked as received',
            'under_review' => 'Report under review',
            'returned' => 'Report returned for revision',
            'approved' => 'Report approved',
            'overdue' => 'Report overdue',
            'reminder' => 'Report submission reminder',
            default => 'Report update',
        };
    }

    private function message(): string
    {
        $subject = $this->report->title ?? ('Report #' . $this->report->id);

        return match ($this->action) {
            'submitted' => sprintf('%s submitted "%s" for review.', $this->actor->name, $subject),
            'resubmitted' => sprintf('%s resubmitted "%s" after revision.', $this->actor->name, $subject),
            'received' => sprintf('%s marked "%s" as received.', $this->actor->name, $subject),
            'under_review' => sprintf('%s is reviewing "%s"%s', $this->actor->name, $subject, $this->comment ? ': ' . $this->comment : '.'),
            'returned' => sprintf('%s returned "%s" for revision%s', $this->actor->name, $subject, $this->comment ? ': ' . $this->comment : '.'),
            'approved' => sprintf('%s approved "%s".', $this->actor->name, $subject),
            'overdue' => sprintf('"%s" is overdue. Outstanding submissions are blocking compilation.', $subject),
            'reminder' => sprintf('Reminder: "%s" has not been submitted yet.', $subject),
            default => sprintf('Update on "%s".', $subject),
        };
    }
}
