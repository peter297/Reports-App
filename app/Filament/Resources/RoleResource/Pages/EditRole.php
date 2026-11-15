<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** @var array<int, string> */
    private array $permissionState = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord()->load('permissions');

        $states = [];

        foreach (array_keys(RoleResource::permissionGroups()) as $key) {
            $states[$key] = [];
        }

        foreach ($record->permissions as $permission) {
            $key = RoleResource::sectionKeyFor($permission->name);

            if ($key !== null) {
                $states[$key][] = $permission->name;
            }
        }

        $data['permissions'] = $states;

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
}
