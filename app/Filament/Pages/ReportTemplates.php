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

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Resources';

    protected static ?string $navigationLabel = 'Download Templates';

    protected static ?string $title = 'Download Templates';

    protected static ?string $slug = 'available-templates';

    protected static string $view = 'filament.pages.report-templates';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('view_any_report::template')) ?? false;
    }

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

                    Forms\Components\Select::make('category')
                        ->options(collect(\App\Models\ReportTemplate::CATEGORIES)
                            ->mapWithKeys(fn (string $category): array => [$category => $category])
                            ->all())
                        ->default('Report Template')
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('link_url')
                        ->label('Tracker Link (Google Sheet URL)')
                        ->url()
                        ->visible(fn (Forms\Get $get): bool => $get('category') === 'Trackers')
                        ->required(fn (Forms\Get $get): bool => $get('category') === 'Trackers'),

                    Forms\Components\Select::make('file_type')
                        ->label('File Type')
                        ->options([
                            'word' => 'Word Document (.docx)',
                            'excel' => 'Excel Spreadsheet (.xlsx)',
                        ])
                        ->default('word')
                        ->required(fn (Forms\Get $get): bool => $get('category') !== 'Trackers'),

                    Forms\Components\FileUpload::make('file_path')
                        ->label('Template File')
                        ->disk('public')
                        ->directory('report-templates')
                        ->maxSize(10240)
                        ->required(fn (Forms\Get $get): bool => $get('category') !== 'Trackers')
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

                    $isTracker = ($data['category'] ?? 'Report Template') === 'Trackers';
                    $filePath = $data['file_path'] ?? null;

                    if ($filePath) {
                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                        if (! in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])) {
                            Notification::make()
                                ->title('Invalid file type')
                                ->body('Only Word (.doc, .docx) and Excel (.xls, .xlsx) files are accepted.')
                                ->danger()
                                ->send();

                            return;
                        }
                    } elseif (! $isTracker) {
                        Notification::make()
                            ->title('Template file required')
                            ->body('Upload a file or choose the Trackers category with a link.')
                            ->danger()
                            ->send();

                        return;
                    }

                    ReportTemplate::create([
                        'name' => $data['name'],
                        'category' => $data['category'] ?? 'Report Template',
                        'file_type' => $data['file_type'] ?? null,
                        'description' => $data['description'] ?? null,
                        'file_path' => $filePath,
                        'link_url' => $data['link_url'] ?? null,
                        'file_size' => $filePath && $disk->exists($filePath) ? $disk->size($filePath) : null,
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

        abort_unless(
            auth()->user()?->can('delete', $template) ?? false,
            403
        );

        if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
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
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        return ReportTemplate::query()->visibleTo($user)->latest()->get();
    }

    public function isSuperAdmin(): bool
    {
        return auth()->user()?->hasUnrestrictedAccess() ?? false;
    }
}
