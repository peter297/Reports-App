<?php

namespace App\Filament\Resources\CoCurricularResource\Pages;

use App\Filament\Resources\CoCurricularResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoCurriculars extends ListRecords
{
    protected static string $resource = CoCurricularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
