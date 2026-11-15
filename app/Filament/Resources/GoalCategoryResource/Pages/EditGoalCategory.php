<?php

namespace App\Filament\Resources\GoalCategoryResource\Pages;

use App\Filament\Resources\GoalCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoalCategory extends EditRecord
{
    protected static string $resource = GoalCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
