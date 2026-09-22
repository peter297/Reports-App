<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected int $createdCount = 0;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if (! $user?->hasUnrestrictedAccess()) {
            $branch = (string) ($data['branch'] ?? '');
            $section = (string) ($data['section'] ?? '');

            abort_unless($user?->canManageAttendance($branch, $section), 403);

            foreach ($data['entries'] ?? [] as $entry) {
                abort_unless($user?->canManageAttendance(
                    $branch,
                    $section,
                    isset($entry['class_id']) ? (int) $entry['class_id'] : null,
                    isset($entry['stream_id']) ? (int) $entry['stream_id'] : null,
                ), 403);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $entries = $data['entries'] ?? [];
        unset($data['entries']);

        $record = null;

        foreach ($entries as $entry) {
            $total = (int) ($entry['total_boys'] ?? 0) + (int) ($entry['total_girls'] ?? 0);
            $absent = min((int) ($entry['total_absent'] ?? 0), $total);

            $record = static::getModel()::create([
                'year_session_id' => $data['year_session_id'] ?? null,
                'term_id' => $data['term_id'] ?? null,
                'week_id' => $data['week_id'] ?? null,
                'attendance_date' => $data['attendance_date'] ?? null,
                'branch' => $data['branch'] ?? null,
                'section' => $data['section'] ?? null,
                'class_id' => $entry['class_id'],
                'stream_id' => $entry['stream_id'],
                'total_boys' => $entry['total_boys'] ?? 0,
                'total_girls' => $entry['total_girls'] ?? 0,
                'total_absent' => $absent,
                'total_present' => max(0, $total - $absent),
            ]);
        }

        $this->createdCount = count($entries);

        return $record ?? static::getModel()::create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Attendance recorded.')
            ->body($this->createdCount > 1
                ? "{$this->createdCount} attendance records were created successfully."
                : 'The attendance record was created successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
