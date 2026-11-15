<?php

namespace App\Filament\Resources\OrganizationalReportResource\Pages;

use App\Filament\Resources\OrganizationalReportResource;
use App\Models\Report;
use App\Models\ReportTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateOrganizationalReport extends CreateRecord
{
    protected static string $resource = OrganizationalReportResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Create a report')
                ->description('Choose the template assigned to your role, then adjust the reporting period.')
                ->schema([
                    Forms\Components\Select::make('template_id')
                        ->label('Report template')
                        ->options(fn (): array => static::availableTemplates())
                        ->searchable()
                        ->required()
                        ->helperText(fn () => static::availableTemplates() === []
                            ? 'No report template is assigned to your role yet. Ask the Super Admin to assign one.'
                            : 'Only templates assigned to your role are shown.'),
                    Forms\Components\TextInput::make('title')
                        ->label('Report title')
                        ->maxLength(255)
                        ->placeholder('Leave blank to use the template name and month'),
                    Forms\Components\DatePicker::make('period_start')
                        ->label('Period start')
                        ->default(now()->startOfMonth())
                        ->required(),
                    Forms\Components\DatePicker::make('period_end')
                        ->label('Period end')
                        ->default(now()->endOfMonth())
                        ->required()
                        ->after('period_start'),
                    Forms\Components\DatePicker::make('deadline')
                        ->label('Submission deadline')
                        ->helperText('Optional. Overdue reports are flagged to the recipients.'),
                ])->columns(2),
        ]);
    }

    protected static function availableTemplates(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $roleIds = $user->roles()->pluck('roles.id')->all();

        if ($roleIds === []) {
            return [];
        }

        return ReportTemplate::query()
            ->where('is_active', true)
            ->whereIn('role_id', $roleIds)
            ->whereNotNull('current_version_id')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        abort_unless($user && $user->can('reports.create'), 403);

        $template = ReportTemplate::with('currentVersion.sections')->findOrFail($data['template_id']);

        $report = app(\App\Services\ReportSubmissionService::class)->createFromTemplate(
            $user,
            $template,
            $data['period_start'] ? Carbon::parse($data['period_start'])->toDateString() : null,
            $data['period_end'] ? Carbon::parse($data['period_end'])->toDateString() : null,
            $data['deadline'] ? Carbon::parse($data['deadline'])->toDateString() : null,
        );

        if (! empty($data['title'])) {
            $report->forceFill(['title' => $data['title']])->save();
        }

        return $report;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
