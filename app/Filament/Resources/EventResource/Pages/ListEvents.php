<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('monthQuotes')
                ->label('Month Quotes')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->visible(fn (): bool => (bool) auth()->user()?->hasSystemAccess())
                ->url(fn (): string => EventResource::getUrl('quotes')),
            Actions\CreateAction::make()
                ->label('Add Event'),
        ];
    }
}
