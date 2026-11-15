<?php

namespace App\Filament\Resources\ReportTemplateResource\Pages;

use App\Filament\Resources\ReportTemplateResource;
use App\Models\ReportTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateReportTemplate extends CreateRecord
{
    protected static string $resource = ReportTemplateResource::class;

    protected function handleRecordCreation(array $data): ReportTemplate
    {
        $templateData = collect($data)->except(['sections', 'change_note'])->all();

        /** @var ReportTemplate $record */
        $record = static::getModel()::create($templateData);

        $sections = $this->sectionsFromState();
        $record->publishVersion($sections, $data['change_note'] ?? 'Initial version', auth()->id());

        return $record;
    }

    /** @return array<int, array{title: string, sort_order?: int, is_mandatory?: bool, is_auto?: bool, data_source?: ?string, help_text?: ?string}> */
    private function sectionsFromState(): array
    {
        $state = $this->form->getRawState();

        return array_map(
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
    }
}
