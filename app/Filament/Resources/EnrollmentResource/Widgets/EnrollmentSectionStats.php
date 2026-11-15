<?php

namespace App\Filament\Resources\EnrollmentResource\Widgets;

use App\Models\Enrollment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EnrollmentSectionStats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $totals = Enrollment::sectionTotals(
            Enrollment::query()->accessibleTo($user)->latest()->get()
        );

        $stats = [];

        foreach ($totals['sections'] as $section => $figures) {
            $stats[] = Stat::make($section, number_format($figures['total']).' learners')
                ->description("{$figures['boys']} boys · {$figures['girls']} girls · +{$figures['admitted']} admitted · −{$figures['left']} left")
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        $overall = $totals['overall'];

        $stats[] = Stat::make('Overall Total', number_format($overall['total']).' learners')
            ->description("{$overall['boys']} boys · {$overall['girls']} girls · +{$overall['admitted']} admitted · −{$overall['left']} left")
            ->descriptionIcon('heroicon-m-globe-alt')
            ->color('success');

        return $stats;
    }
}
