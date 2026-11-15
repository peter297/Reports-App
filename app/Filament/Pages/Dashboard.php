<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationGroup = 'Overview';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dashboard';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('dashboard.view');
    }
}
