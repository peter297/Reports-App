<?php

namespace App\Filament\Resources\SyllabusMappingResource\Pages;

use App\Filament\Resources\SyllabusMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSyllabusMappings extends ListRecords
{
    protected static string $resource = SyllabusMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => SyllabusMappingResource::canCreate()),
        ];
    }
}
