<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->hasUnrestrictedAccess()) {
            $data['branch'] = auth()->user()?->branch;
        }

        $data['class_breakdown'] = array_map(
            fn (array $class): array => [
                ...$class,
                'boys' => (int) ($class['boys'] ?? 0),
                'girls' => (int) ($class['girls'] ?? 0),
                'total' => (int) ($class['boys'] ?? 0) + (int) ($class['girls'] ?? 0),
                'new_admissions' => (int) ($class['new_admissions'] ?? 0),
                'departures' => (int) ($class['departures'] ?? 0),
            ],
            $data['class_breakdown'] ?? [],
        );
        $data['total_learners'] = collect($data['class_breakdown'])->sum('total');
        $data['current_enrollment'] = $data['total_learners'];
        $data['new_admissions'] = collect($data['class_breakdown'])->sum('new_admissions');
        $data['departures'] = collect($data['class_breakdown'])->sum('departures');

        return $data;
    }
}
