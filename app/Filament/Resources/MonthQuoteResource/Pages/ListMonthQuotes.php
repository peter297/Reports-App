<?php

namespace App\Filament\Resources\MonthQuoteResource\Pages;

use App\Filament\Resources\MonthQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthQuotes extends ListRecords
{
    protected static string $resource = MonthQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
