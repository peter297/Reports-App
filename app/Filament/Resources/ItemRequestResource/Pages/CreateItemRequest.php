<?php

namespace App\Filament\Resources\ItemRequestResource\Pages;

use App\Filament\Resources\ItemRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateItemRequest extends CreateRecord
{
    protected static string $resource = ItemRequestResource::class;

    protected function getRedirectUrl(): string
    {

    return $this->getResource()::getUrl('index');

    }
}
