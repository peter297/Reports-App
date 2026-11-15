<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

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
