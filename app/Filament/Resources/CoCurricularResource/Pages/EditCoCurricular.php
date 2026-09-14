<?php

namespace App\Filament\Resources\CoCurricularResource\Pages;

use App\Filament\Resources\CoCurricularResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoCurricular extends EditRecord
{
    protected static string $resource = CoCurricularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
