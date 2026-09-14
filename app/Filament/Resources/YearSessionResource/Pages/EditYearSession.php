<?php

namespace App\Filament\Resources\YearSessionResource\Pages;

use App\Filament\Resources\YearSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYearSession extends EditRecord
{
    protected static string $resource = YearSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
