<?php

namespace App\Filament\Resources\GoalSheetResource\Pages;

use App\Filament\Resources\GoalSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoalSheets extends ListRecords
{
    protected static string $resource = GoalSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Goal Sheet'),
        ];
    }
}
