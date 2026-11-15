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
use Livewire\Features\SupportFileUploads\WithFileUploads;

class EditOrganizationalReport extends Page
{
    use WithFileUploads;

    protected static string $resource = OrganizationalReportResource::class;

    protected static string $view = 'filament.resources.organizational-report-resource.pages.edit-organizational-report';

    public Report $record;

    /** @var array<int, array{id: int, title: string, content: ?string, is_mandatory: bool, is_auto: bool, data_source: ?string, sort_order: int}> */
    public array $responses = [];

    public string $note = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public function mount(Report $record): void
    {
        $user = Auth::user();

        abort_unless($user, 403);
        abort_unless($user->can('reports.create') || $user->can('reports.module.view'), 403);
        abort_unless($record->isEditableBy($user) || $user->isSuperAdmin(), 403, 'This report can no longer be edited.');

        $this->record = $record->load(['user', 'branch', 'academicLevel', 'template', 'templateVersion', 'responses', 'attachments']);
        $this->loadResponses();
    }

    public function getTitle(): string
    {
        return 'Edit: ' . ($this->record->title ?? 'Report');
    }

    public function getBreadcrumbs(): array
    {
        return ['Reports', $this->record->title ?? 'Report'];
    }

    public function getStatusProperty(): ReportStatus
    {
        return $this->record->status;
    }

    /** @return array<int, string> */
    public function getDataSourceLabelsProperty(): array
    {
        return \App\Services\ReportDataSources::LABELS;
    }

    protected function loadResponses(): void
    {
        $this->responses = $this->record->responses
            ->sortBy('sort_order')
            ->map(fn (ReportSectionResponse $response): array => [
                'id' => $response->id,
                'title' => $response->title,
                'content' => $response->content,
                'is_mandatory' => $response->is_mandatory,
                'is_auto' => (bool) $response->data_source,
                'data_source' => $response->data_source,
                'sort_order' => $response->sort_order,
            ])
            ->values()
            ->all();
    }

    public function saveDraft(): void
    {
        $this->persist();

        Notification::make()
            ->title('Draft saved')
            ->success()
            ->send();
    }

    public function refreshAutoSection(int $responseId): void
    {
        $actor = $this->record->user;

        abort_unless($actor, 422, 'This report has no submitter.');

        $response = $this->record->responses()->findOrFail($responseId);

        if ($response->data_source) {
            $resolved = app(\App\Services\ReportDataSources::class)->resolve($response->data_source, $actor);
            $response->forceFill(['content' => $resolved['content'], 'auto_payload' => $resolved['payload']])->save();
        }

        $this->record->load('responses');
        $this->loadResponses();

        Notification::make()->title('Section refreshed from system data')->success()->send();
    }

    public function refreshAllAutoSections(): void
    {
        app(ReportSubmissionService::class)->refreshAutoSections($this->record);

        $this->record->load('responses');
        $this->loadResponses();

        Notification::make()->title('Automatic sections refreshed')->success()->send();
    }

    public function submitReport()
    {
        $this->persist();

        try {
            app(ReportSubmissionService::class)->submit($this->record->refresh(), Auth::user(), filled($this->note) ? trim($this->note) : null);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
                ->title('Cannot submit yet')
                ->body(implode(' ', $exception->validator->errors()->all()))
                ->danger()
                ->send();

            return null;
        }

        Notification::make()
            ->title('Report submitted')
            ->body('Your line manager has been notified.')
            ->success()
            ->send();

        return redirect()->to(OrganizationalReportResource::getUrl('view', ['record' => $this->record->id]));
    }

    public function uploadAttachment(): void
    {
        $this->validate([
            'uploads.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt'],
        ]);

        foreach ($this->uploads as $upload) {
            $path = $upload->store('report-attachments/' . $this->record->id, 'public');

            $this->record->attachments()->create([
                'uploaded_by' => Auth::id(),
                'file_path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'mime_type' => $upload->getMimeType(),
                'size_bytes' => $upload->getSize(),
            ]);
        }

        $this->uploads = [];
        $this->record->load('attachments');

        Notification::make()->title('Attachment(s) added')->success()->send();
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = $this->record->attachments()->findOrFail($attachmentId);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        $this->record->load('attachments');
    }

    protected function persist(): void
    {
        $ids = array_column($this->responses, 'id');
        $byId = $this->record->responses()->whereIn('id', $ids)->get()->keyBy('id');

        foreach ($this->responses as $response) {
            $model = $byId->get($response['id']);

            $content = \App\Services\ReportSubmissionService::sanitize($response['content'] ?? null);

            if ($model && $model->content !== $content) {
                $model->forceFill(['content' => $content])->save();
            }
        }

        $this->record->refresh();
    }
}
