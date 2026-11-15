<?php

namespace App\Filament\Resources\SyllabusMappingResource\Pages;

use App\Filament\Resources\SyllabusMappingResource;
use App\Models\SyllabusMapping;
use App\Support\SyllabusActivityLogger;
use App\Support\SyllabusStructure;

class CreateSyllabusMapping extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = SyllabusMappingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $candidate = new SyllabusMapping($data);

        if ($candidate->duplicateExists()) {
            \Filament\Notifications\Notification::make()
                ->title('A draft or active mapping already exists for this session, term, class, stream and subject.')
                ->danger()
                ->send();

            throw new \Filament\Support\Exceptions\Halt;
        }

        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $teacherId = $this->form->getState()['assigned_teacher_id'] ?? null;

        if ($teacherId) {
            $this->upsertTeacherAssignment((int) $teacherId);
        }

        $state = $this->form->getState();
        $strands = $state['strands'] ?? [];

        app(SyllabusStructure::class)->sync($this->getRecord(), $strands);

        SyllabusActivityLogger::record('syllabus_mapping', $this->getRecord()->id, 'created', null, [
            'year_session_id' => $this->getRecord()->year_session_id,
            'term_id' => $this->getRecord()->term_id,
            'class_id' => $this->getRecord()->class_id,
            'stream_id' => $this->getRecord()->stream_id,
            'subject_id' => $this->getRecord()->subject_id,
            'total_lessons' => $this->getRecord()->total_lessons,
        ]);
    }

    protected function upsertTeacherAssignment(int $teacherId): void
    {
        $mapping = $this->getRecord();
        $branch = $mapping->class?->branch;
        $stream = $mapping->stream;

        if (! $branch) {
            return;
        }

        \App\Models\SubjectAssignment::query()->updateOrCreate(
            [
                'year_session_id' => $mapping->year_session_id,
                'subject_id' => $mapping->subject_id,
                'class_id' => $mapping->class_id,
                'user_id' => $teacherId,
                'assignment_role' => \App\Models\SubjectAssignment::ROLE_TEACHER,
            ],
            [
                'stream_id' => $stream?->id,
                'branch' => $branch->name,
            ],
        );
    }
}
