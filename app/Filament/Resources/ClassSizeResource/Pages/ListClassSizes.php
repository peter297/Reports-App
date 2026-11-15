<?php

namespace App\Filament\Resources\ClassSizeResource\Pages;

use App\Filament\Resources\ClassSizeResource;
use App\Filament\Resources\ClassSizeResource\Widgets;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListClassSizes extends ListRecords
{
    protected static string $resource = ClassSizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Forms\Components\Select::make('year_session_id')
                        ->label('Academic Year')
                        ->relationship('yearSession', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => \App\Models\YearSession::active()?->id)
                        ->required(),
                    Forms\Components\Select::make('term_id')
                        ->label('Term')
                        ->relationship('term', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => \App\Models\Term::active()?->id)
                        ->required(),
                ])
                ->action(function (array $data, \Livewire\Component $livewire): void {
                    $livewire->redirect(route('class-sizes.report.pdf', [
                        'yearSession' => $data['year_session_id'],
                        'term' => $data['term_id'],
                    ]));
                })
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\ClassSizeStats::class,
        ];
    }
}
