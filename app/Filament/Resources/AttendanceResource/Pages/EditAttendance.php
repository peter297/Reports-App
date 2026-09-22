<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if (! $user?->hasUnrestrictedAccess()) {
            abort_unless($user?->canManageAttendance(
                (string) ($data['branch'] ?? $user?->branch),
                (string) ($data['section'] ?? ''),
                isset($data['class_id']) ? (int) $data['class_id'] : null,
                isset($data['stream_id']) ? (int) $data['stream_id'] : null,
            ), 403);
        }

        $total = (int) ($data['total_boys'] ?? 0) + (int) ($data['total_girls'] ?? 0);
        $absent = min((int) ($data['total_absent'] ?? 0), $total);

        $data['total_absent'] = $absent;
        $data['total_present'] = max(0, $total - $absent);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
