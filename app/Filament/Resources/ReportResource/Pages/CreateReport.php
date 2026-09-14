<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function getRedirectUrl(): string
    {

    return $this->getResource()::getUrl('index');

    }

    protected function getCreatedNotification(): ?Notification {
        return Notification::make()
            ->success()
            ->title('Report Submited.')
            ->body('The Report is Submitted Successfully!');
    }
}
