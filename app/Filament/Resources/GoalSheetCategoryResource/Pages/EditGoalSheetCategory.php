<?php

namespace App\Filament\Resources\GoalSheetCategoryResource\Pages;

use App\Filament\Resources\GoalSheetCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoalSheetCategory extends EditRecord
{
    protected static string $resource = GoalSheetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
