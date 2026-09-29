<?php

namespace App\Filament\Resources\AcademicAnalysisResource\Pages;

use App\Filament\Resources\AcademicAnalysisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicAnalysis extends CreateRecord
{
    protected static string $resource = AcademicAnalysisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
