<?php

namespace App\Filament\Resources\EventResource\Widgets;
use Filament\Widgets\ChartWidget;
use App\Models\Event;
use Filament\Widgets\BarChartWidget;

class EventPerTermChart extends BarChartWidget
{
    protected static ?string $heading = 'Events Per Term';

    protected function getData(): array
    {
        $terms = ['Term 1', 'Term 2', 'Term 3'];

        $eventCounts = Event::query()
            ->selectRaw('term, COUNT(*) as count')
            ->groupBy('term')
            ->pluck('count', 'term')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Events',
                    'data' => array_map(fn ($term) => $eventCounts[$term] ?? 0, $terms),
                    'backgroundColor' => ['#ff6384', '#36a2eb', '#ffce56'], // Colors for bars
                ],
            ],
            'labels' => $terms,
        ];
    }
}

