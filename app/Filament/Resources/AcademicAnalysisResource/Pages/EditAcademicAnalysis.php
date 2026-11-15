<?php

namespace App\Filament\Resources\AcademicAnalysisResource\Pages;

use App\Filament\Resources\AcademicAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicAnalysis extends EditRecord
{
    protected static string $resource = AcademicAnalysisResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if (! $user?->hasUnrestrictedAccess() && ! $user?->isHead()) {
            if ($user?->isDeputy()) {
                abort_unless(($data['branch'] ?? null) === $user?->branch, 403);
            } else {
                abort_unless($user?->canManageAttendance(
                    (string) ($data['branch'] ?? ''),
                    (string) ($data['section'] ?? ''),
                    isset($data['class_id']) ? (int) $data['class_id'] : null,
                    isset($data['stream_id']) ? (int) $data['stream_id'] : null,
                ), 403);
            }
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
