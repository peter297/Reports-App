<?php

namespace App\Filament\Resources\GoalSheetResource\Pages;

use App\Filament\Resources\GoalSheetResource;
use App\Models\GoalSheet;
use Filament\Resources\Pages\CreateRecord;

class CreateGoalSheet extends CreateRecord
{
    protected static string $resource = GoalSheetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // New sheets always start as drafts owned by the current user when left blank.
        if (empty($data['staff_user_id']) && empty($data['staff_name'])) {
            $data['staff_name'] = auth()->user()?->name;
        }

        $data['status'] = GoalSheet::STATUS_DRAFT;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
