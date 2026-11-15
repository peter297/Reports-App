<?php

namespace App\Console\Commands;

use App\Enums\ReportFrequency;
use App\Models\Report;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Notifications\ReportWorkflowNotification;
use App\Services\ReportRouting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class MarkReportsOverdue extends Command
{
    protected $signature = 'reports:mark-overdue {--date= : Reference date (defaults to today)}';

    protected $description = 'Notify submitters with an outstanding report and alert routed recipients about overdue submissions.';

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $todayDate = $today->toDateString();

        $reminded = 0;

        $templates = ReportTemplate::query()
            ->with('role')
            ->where('is_active', true)
            ->whereNotNull('current_version_id')
            ->get();

        foreach ($templates as $template) {
            if (! $template->role || ! $template->frequency instanceof ReportFrequency) {
                continue;
            }

            [$periodStart, $periodEnd] = $template->frequency->periodFor($today);

            // A template is overdue once its period has ended and no non-draft report exists for it.
            if ($periodEnd->gte($today->copy()->startOfDay())) {
                continue;
            }

            $submitters = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('roles.id', $template->role_id))
                ->get();

            foreach ($submitters as $submitter) {
                $existing = Report::query()
                    ->where('user_id', $submitter->id)
                    ->where('template_id', $template->id)
                    ->whereDate('period_start', '<=', $periodEnd->toDateString())
                    ->whereDate('period_end', '>=', $periodStart->toDateString())
                    ->whereNotIn('status', ['draft', 'archived'])
                    ->exists();

                if ($existing) {
                    continue;
                }

                // Remind the submitter (once per day).
                $submitter->notify(new ReportWorkflowNotification(
                    $this->placeholderReport($template, $submitter, $periodStart, $periodEnd),
                    $submitter,
                    'reminder',
                    null,
                    \App\Filament\Resources\OrganizationalReportResource::getUrl('create'),
                ));

                // Alert routed recipients about the outstanding submission.
                $recipients = app(ReportRouting::class)->recipientsFor($submitter);

                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, new ReportWorkflowNotification(
                        $this->placeholderReport($template, $submitter, $periodStart, $periodEnd),
                        $submitter,
                        'overdue',
                        null,
                        \App\Filament\Resources\OrganizationalReportResource::getUrl('index'),
                    ));
                }

                $reminded++;
            }
        }

        $this->info("Reminded {$reminded} submitter(s) about outstanding report(s).");

        return self::SUCCESS;
    }

    private function placeholderReport(ReportTemplate $template, User $submitter, $periodStart, $periodEnd): Report
    {
        $report = new Report([
            'title' => $template->name . ' — ' . Carbon::parse($periodEnd)->format('F Y'),
            'user_id' => $submitter->id,
            'template_id' => $template->id,
        ]);
        $report->id = 0; // placeholder; notification URL points at the resource index
        $report->period_start = $periodStart;
        $report->period_end = $periodEnd;

        return $report;
    }
}
