<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** @var array<int, string> */
    private array $permissionState = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissionState = collect($data['permissions'] ?? [])
            ->flatten()
            ->unique()
            ->values()
            ->all();

        return collect($data)->except(['permissions'])->all();
    }

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->permissionState);
    }
}
