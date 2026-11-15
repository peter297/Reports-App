<?php

namespace App\Filament\Resources\ReportTemplateResource\Pages;

use App\Filament\Resources\ReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReportTemplate extends EditRecord
{
    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function fillForm(): void
    {
        $record = $this->getRecord();
        $record->loadMissing('currentVersion.sections');

        $data = $record->attributesToArray();
        $data['sections'] = $record->currentVersion?->sections->map(fn ($section) => [
            'title' => $section->title,
            'data_source' => $section->data_source,
            'is_mandatory' => $section->is_mandatory,
            'is_auto' => $section->is_auto,
            'help_text' => $section->help_text,
        ])->all() ?? [];

        $this->form->fill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return collect($data)->except(['sections', 'change_note'])->all();
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $state = $this->form->getRawState();

        $sections = array_map(
            fn (array $section, int $index): array => [
                'title' => $section['title'],
                'sort_order' => $index + 1,
                'is_mandatory' => (bool) ($section['is_mandatory'] ?? true),
                'is_auto' => (bool) ($section['is_auto'] ?? false),
                'data_source' => $section['data_source'] ?? null,
                'help_text' => $section['help_text'] ?? null,
            ],
            $state['sections'] ?? [],
            array_keys($state['sections'] ?? []),
        );

        $note = $state['change_note'] ?? null;

        if ($sections !== []) {
            $record->publishVersion($sections, $note, auth()->id());
        }
    }
}
