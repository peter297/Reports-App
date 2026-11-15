<?php

namespace App\Filament\Resources\SyllabusMappingResource\Pages;

use App\Filament\Resources\SyllabusMappingResource;
use App\Support\SyllabusActivityLogger;
use App\Support\SyllabusStructure;
use Filament\Resources\Pages\EditRecord;

class EditSyllabusMapping extends EditRecord
{
    protected static string $resource = SyllabusMappingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(SyllabusStructure::class)->guardAgainstDestructiveChanges(
            $this->getRecord(),
            $data['strands'] ?? [],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $state = $this->form->getState();
        $strands = $state['strands'] ?? [];

        app(SyllabusStructure::class)->sync($this->getRecord(), $strands);

        $teacherId = isset($state['assigned_teacher_id']) ? (int) $state['assigned_teacher_id'] : null;

        if ($teacherId) {
            $this->upsertTeacherAssignment($teacherId);
        }

        SyllabusActivityLogger::record('syllabus_mapping', $this->getRecord()->id, 'updated', null, [
            'total_lessons' => $this->getRecord()->total_lessons,
            'status' => $this->getRecord()->status?->value,
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

    /**
     * Prefill the repeater with the existing structure.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $mapping = $this->getRecord()->load('strands.substrands.lessons');

        $data['strands'] = $mapping->strands->map(fn ($strand) => [
            'id' => $strand->id,
            'name' => $strand->name,
            'description' => $strand->description,
            'expected_completion_date' => $strand->expected_completion_date?->toDateString(),
            'substrands' => $strand->substrands->map(fn ($substrand) => [
                'id' => $substrand->id,
                'name' => $substrand->name,
                'description' => $substrand->description,
                'lessons' => $substrand->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'topic' => $lesson->topic,
                ])->all(),
            ])->all(),
        ])->all();

        $data['assigned_teacher_id'] = $mapping->assignedTeacher()?->id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sheet')
                ->label('Open progress sheet')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn (): string => SyllabusMappingResource::getUrl('sheet', ['record' => $this->getRecord()])),
            \Filament\Actions\Action::make('review')
                ->label('Coordinator review')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->url(fn (): string => SyllabusMappingResource::getUrl('review', ['record' => $this->getRecord()])),
        ];
    }
}
