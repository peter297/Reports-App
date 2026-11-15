<?php

namespace App\Filament\Resources\YearSessionResource\Pages;

use App\Filament\Resources\YearSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYearSession extends EditRecord
{
    protected static string $resource = YearSessionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['terms'] = YearSessionResource::hydrateTerms($this->getRecord());

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        YearSessionResource::validateWeekNumbers($data['terms'] ?? []);

        unset($data['terms']);

        return $data;
    }

    protected function afterSave(): void
    {
        YearSessionResource::syncTerms($this->getRecord(), $this->form->getState()['terms'] ?? []);
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->terms()->count() === 0),
        ];

        return $actions;
    }
}
