<?php

namespace App\Filament\Resources\YearSessionResource\Pages;

use App\Filament\Resources\YearSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYearSessions extends ListRecords
{
    protected static string $resource = YearSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
