<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array<int, string> */
    private array $permissionState = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord()->load('permissions');

        $data['permissions'] = $record->permissions->pluck('name')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissionState = collect($data['permissions'] ?? [])
            ->flatten()
            ->unique()
            ->values()
            ->all();

        return collect($data)->except(['permissions'])->all();
    }

    protected function afterSave(): void
    {
        $this->record->syncPermissions($this->permissionState);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
