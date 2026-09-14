<?php

namespace App\Filament\Resources\SectionCoordinatorAssignmentResource\Pages;

use App\Filament\Resources\SectionCoordinatorAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSectionCoordinatorAssignments extends ListRecords
{
    protected static string $resource = SectionCoordinatorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
