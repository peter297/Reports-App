<?php

namespace App\Filament\Resources\ExtraCurricularResource\Pages;

use App\Filament\Resources\ExtraCurricularResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtraCurriculars extends ListRecords
{
    protected static string $resource = ExtraCurricularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
