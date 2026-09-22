<?php

namespace App\Filament\Resources\ClassSizeResource\Pages;

use App\Filament\Resources\ClassSizeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassSize extends EditRecord
{
    protected static string $resource = ClassSizeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if (! $user?->hasUnrestrictedAccess()) {
            abort_unless($user?->canManageAttendance(
                (string) ($data['branch'] ?? ''),
                (string) ($data['section'] ?? ''),
                isset($data['class_id']) ? (int) $data['class_id'] : null,
                isset($data['stream_id']) ? (int) $data['stream_id'] : null,
            ), 403);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
