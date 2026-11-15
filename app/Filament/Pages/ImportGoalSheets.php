<?php

namespace App\Filament\Pages;

use App\Models\GoalSheetImport;
use App\Services\GoalSheetImporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class ImportGoalSheets extends Page
{
    use WithFileUploads;

    protected static string $view = 'filament.pages.import-goal-sheets';

    protected static ?string $navigationGroup = 'Goal Sheets';

    protected static ?string $navigationLabel = 'Import Workbook';

    protected static ?string $title = 'Import Goal Sheets Workbook';

    protected static ?int $navigationSort = 3;

    public $workbook = null;

    public bool $dryRun = true;

    public ?int $lastImportId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('goalsheets.admin'), 403);
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('goalsheets.admin') ?? false;
    }

    public function import(): void
    {
        $user = Auth::user();

        abort_unless($user?->can('goalsheets.admin'), 403);

        $this->validate([
            'workbook' => ['required', 'file', 'max:20480', 'mimes:xlsx'],
        ]);

        $path = $this->workbook->store('goal-sheet-imports');
        $filename = $this->workbook->getClientOriginalName();

        try {
            $import = app(GoalSheetImporter::class)->importFromPath(
                Storage::path($path),
                $filename,
                $user,
                $this->dryRun,
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Import failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->lastImportId = $import->id;
        $this->workbook = null;

        $summary = $import->summary ?? [];

        Notification::make()
            ->title($this->dryRun ? 'Dry run complete — nothing was written' : 'Workbook imported')
            ->body(sprintf(
                '%d sheet(s) created, %d updated, %d failed, %d warnings.',
                $summary['created'] ?? 0,
                $summary['updated'] ?? 0,
                $summary['failed'] ?? 0,
                $summary['warnings'] ?? 0,
            ))
            ->success()
            ->send();
    }

    public function getLastImportProperty(): ?GoalSheetImport
    {
        if ($this->lastImportId) {
            return GoalSheetImport::query()->with('uploadedBy')->find($this->lastImportId);
        }

        return GoalSheetImport::query()->with('uploadedBy')->latest()->first();
    }

    /** @return \Illuminate\Support\Collection<int, GoalSheetImport> */
    public function getHistoryProperty()
    {
        return GoalSheetImport::query()->with('uploadedBy')->latest()->limit(10)->get();
    }
}
