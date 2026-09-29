<?php

namespace App\Filament\Resources\AcademicAnalysisResource\Pages;

use App\Filament\Resources\AcademicAnalysisResource;
use App\Filament\Resources\AcademicAnalysisResource\Widgets;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicAnalyses extends ListRecords
{
    protected static string $resource = AcademicAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\SubjectAveragesChart::class,
            Widgets\TermTrendChart::class,
            Widgets\BranchComparisonChart::class,
        ];
    }
}
