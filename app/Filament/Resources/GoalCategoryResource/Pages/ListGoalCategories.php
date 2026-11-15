<?php

namespace App\Filament\Resources\GoalCategoryResource\Pages;

use App\Filament\Resources\GoalCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoalCategories extends ListRecords
{
    protected static string $resource = GoalCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
