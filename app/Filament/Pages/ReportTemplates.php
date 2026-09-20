<?php

namespace App\Filament\Pages;

use App\Models\ReportTemplate;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ReportTemplates extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Resources';

    protected static ?string $navigationLabel = 'Report Templates';

    protected static ?string $title = 'Report Templates';

    protected static ?string $slug = 'available-templates';

    protected static string $view = 'filament.pages.report-templates';

    public function getHeaderActions(): array
    {
        if (! $this->isSuperAdmin()) {
            return [];
        }

        return [
            Action::make('uploadTemplate')
                ->label('Upload Template')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Monthly Attendance Report'),

                    Forms\Components\Select::make('file_type')
                        ->label('File Type')
                        ->options([
                            'word' => 'Word Document (.docx)',
                            'excel' => 'Excel Spreadsheet (.xlsx)',
                        ])
                        ->default('word')
                        ->required(),

                    Forms\Components\FileUpload::make('file_path')
                        ->label('Template File')
                        ->disk('public')
                        ->directory('report-templates')
                        ->maxSize(10240)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            if (! $state) {
                                return;
                            }

                            $disk = Storage::disk('public');
                            $extension = strtolower(pathinfo($disk->path($state), PATHINFO_EXTENSION));

                            $set('file_type', match (true) {
                                in_array($extension, ['doc', 'docx']) => 'word',
                                in_array($extension, ['xls', 'xlsx']) => 'excel',
                                default => 'word',
                            });
                        }),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->nullable()
                        ->rows(2)
                        ->placeholder('Brief description of this template...'),
                ])
                ->action(function (array $data): void {
                    $disk = Storage::disk('public');
                    $filePath = $data['file_path'];
                    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                    if (! in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {
                        Notification::make()
                            ->title('Invalid file type')
                            ->body('Only Word (.doc, .docx) and Excel (.xls, .xlsx) files are accepted.')
                            ->danger()
                            ->send();

                        return;
                    }

                    ReportTemplate::create([
                        'name' => $data['name'],
                        'file_type' => $data['file_type'],
                        'description' => $data['description'] ?? null,
                        'file_path' => $filePath,
                        'file_size' => $disk->size($filePath),
                    ]);

                    Notification::make()
                        ->title('Template uploaded successfully')
                        ->success()
                        ->send();
                })
                ->modalSubmitActionLabel('Upload Template'),
        ];
    }

    public function deleteTemplate(int $templateId): void
    {
        $template = ReportTemplate::findOrFail($templateId);

        if (Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        Notification::make()
            ->title('Template deleted successfully')
            ->success()
            ->send();
    }

    public function getTemplates()
    {
        return ReportTemplate::query()->latest()->get();
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()?->hasUnrestrictedAccess() ?? false;
    }
}
