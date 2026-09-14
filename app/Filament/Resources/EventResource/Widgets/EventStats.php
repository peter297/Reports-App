<?php

namespace App\Filament\Resources\EventResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\BarChartWidget;
use Filament\Widgets\TableWidget as BaseTableWidget;
use Filament\Tables\Columns\TextColumn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class EventStats extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Events', Event::count())
                ->description('All scheduled events')
                ->color('primary'),

            Card::make('Upcoming Events', Event::where('start_date', '>', Carbon::today())->count())
                ->description('Future scheduled events')
                ->color('info'),

            Card::make('Completed Events', Event::where('status', 'Completed')->count())
                ->description('Events marked as completed')
                ->color('success'),
        ];
    }
}



