<?php

namespace App\Filament\Resources\MonthQuoteResource\Pages;

use App\Filament\Resources\MonthQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthQuote extends EditRecord
{
    protected static string $resource = MonthQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
