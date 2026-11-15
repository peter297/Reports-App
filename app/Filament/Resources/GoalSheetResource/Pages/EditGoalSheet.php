<?php

namespace App\Filament\Resources\GoalSheetResource\Pages;

use App\Filament\Resources\GoalSheetResource;
use App\Models\GoalSheet;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditGoalSheet extends EditRecord
{
    protected static string $resource = GoalSheetResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $user = Auth::user();

        abort_unless($user?->can('goalsheets.edit'), 403);

        // Interface AND backend gate: locked sheets bounce to the read-only view.
        if (! $this->record->isEditableBy($user)) {
            Notification::make()
                ->title('This goal sheet is locked')
                ->body('Submitted and acknowledged goal sheets can only be viewed, not edited.')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Status transitions (submit/acknowledge) happen via actions, not the form.
        unset($data['status'], $data['submitted_at']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => static::getResource()::canDelete($this->record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
