<?php

namespace App\Filament\Resources\OrganizationalReportResource\Pages;

use App\Enums\ReportStatus;
use App\Filament\Resources\OrganizationalReportResource;
use App\Models\Report;
use App\Models\ReportSectionResponse;
use App\Services\ReportSubmissionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ViewOrganizationalReport extends Page
{
    protected static string $resource = OrganizationalReportResource::class;

    protected static string $view = 'filament.resources.organizational-report-resource.pages.view-organizational-report';

    public Report $record;

    public string $comment = '';

    public string $reviewComment = '';

    public function mount(Report $record): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $canView = $record->user_id === $user->id
            || $record->recipients()->where('users.id', $user->id)->exists()
            || $user->can('reports.view_all')
            || $user->isSuperAdmin();

        abort_unless($canView, 403, 'You are not authorized to view this report.');

        $this->record = $record->load(['user', 'branch', 'academicLevel', 'template', 'templateVersion', 'responses' => fn ($q) => $q->orderBy('sort_order'), 'attachments', 'reviews' => fn ($q) => $q->with('user')->orderBy('created_at'), 'comments' => fn ($q) => $q->with('user')->orderBy('created_at'), 'compilation.sources.sourceReport']);
    }

    public function getTitle(): string
    {
        return $this->record->title ?? 'Report';
    }

    public function getBreadcrumbs(): array
    {
        return ['Reports', $this->record->title ?? 'Report'];
    }

    /** @return array{name: string, email: string}|null */
    public function isRecipientProperty(): bool
    {
        return $this->record->recipients()->where('users.id', Auth::id())->exists();
    }

    public function canReviewProperty(): bool
    {
        return $this->isRecipientProperty()
            || Auth::user()?->can('reports.view_all')
            || Auth::user()?->isSuperAdmin();
    }

    public function addComment(): void
    {
        $this->validate(['comment' => ['required', 'string', 'max:2000']]);

        $user = Auth::user();

        $this->record->comments()->create([
            'user_id' => $user->id,
            'comment_text' => $this->comment,
        ]);

        $this->comment = '';
        $this->record->load('comments');

        Notification::make()->title('Comment added')->success()->send();
    }

    public function markReceived(): void
    {
        $this->review('received');
    }

    public function markUnderReview(): void
    {
        $this->review('under_review');
    }

    public function approveReport(): void
    {
        $this->review('approve');
    }

    public function returnForRevision(): void
    {
        $this->validate(['reviewComment' => ['required', 'string', 'max:2000']]);

        $user = Auth::user();

        try {
            app(ReportSubmissionService::class)->returnForRevision($this->record, $user, $this->reviewComment);
        } catch (ValidationException $exception) {
            Notification::make()->title('Cannot return report')->body(implode(' ', $exception->validator->errors()->all()))->danger()->send();

            return;
        }

        $this->reviewComment = '';
        $this->record->refresh()->load(['reviews.user']);

        Notification::make()->title('Report returned for revision')->warning()->send();
    }

    public function archiveReport(): void
    {
        app(ReportSubmissionService::class)->archive($this->record, Auth::user());

        $this->record->refresh()->load(['reviews.user']);

        Notification::make()->title('Report archived')->success()->send();
    }

    private function review(string $action): void
    {
        $user = Auth::user();
        $service = app(ReportSubmissionService::class);

        try {
            match ($action) {
                'received' => $service->markReceived($this->record, $user),
                'under_review' => $service->markUnderReview($this->record, $user, filled($this->reviewComment) ? $this->reviewComment : null),
                'approve' => $service->approve($this->record, $user, filled($this->reviewComment) ? $this->reviewComment : null),
            };
        } catch (ValidationException $exception) {
            Notification::make()->title('Action failed')->body(implode(' ', $exception->validator->errors()->all()))->danger()->send();

            return;
        }

        $this->reviewComment = '';
        $this->record->refresh()->load(['reviews.user']);

        Notification::make()->title('Report updated')->success()->send();
    }
}
