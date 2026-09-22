<?php

namespace App\Filament\Resources\ClassSizeResource\Widgets;

use App\Helpers\Utils;
use App\Models\ClassSize;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ClassSizeStats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $stats = [];

        $sections = ClassSize::query()
            ->accessibleTo($user)
            ->selectRaw('section, sum(total_boys) as boys, sum(total_girls) as girls, sum(class_total) as total')
            ->groupBy('section')
            ->orderBy('section')
            ->get();

        foreach ($sections as $section) {
            $stats[] = Stat::make($section->section ?: 'Unassigned', number_format((int) $section->total).' learners')
                ->description(((int) $section->boys).' boys · '.((int) $section->girls).' girls')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        if ($user->hasRole(Utils::getSuperAdminName())) {
            $overall = ClassSize::query()
                ->selectRaw('sum(total_boys) as boys, sum(total_girls) as girls, sum(class_total) as total')
                ->first();

            $stats[] = Stat::make('All Sections', number_format((int) $overall->total).' learners')
                ->description(((int) $overall->boys).' boys · '.((int) $overall->girls).' girls')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success');

            $branches = ClassSize::query()
                ->selectRaw('branch, sum(total_boys) as boys, sum(total_girls) as girls, sum(class_total) as total')
                ->groupBy('branch')
                ->orderBy('branch')
                ->get();

            foreach ($branches as $branch) {
                $stats[] = Stat::make($branch->branch ?: 'Unassigned', number_format((int) $branch->total).' learners')
                    ->description(((int) $branch->boys).' boys · '.((int) $branch->girls).' girls')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('warning');
            }
        }

        return $stats;
    }
}
