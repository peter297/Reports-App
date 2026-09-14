<?php

namespace App\Filament\Resources\ExtraCurricularResource\Pages;

use App\Filament\Resources\ExtraCurricularResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtraCurricular extends EditRecord
{
    protected static string $resource = ExtraCurricularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
