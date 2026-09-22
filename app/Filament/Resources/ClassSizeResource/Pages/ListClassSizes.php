<?php

namespace App\Filament\Resources\ClassSizeResource\Pages;

use App\Filament\Resources\ClassSizeResource;
use App\Filament\Resources\ClassSizeResource\Widgets;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassSizes extends ListRecords
{
    protected static string $resource = ClassSizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
