<?php

namespace App\Filament\Resources\YearSessionResource\Pages;

use App\Filament\Resources\YearSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateYearSession extends CreateRecord
{
    protected static string $resource = YearSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        YearSessionResource::validateWeekNumbers($data['terms'] ?? []);

        unset($data['terms']);

        return $data;
    }

    protected function afterCreate(): void
    {
        YearSessionResource::syncTerms($this->getRecord(), $this->form->getState()['terms'] ?? []);
    }
}
