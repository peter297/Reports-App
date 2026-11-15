<?php

namespace App\Enums;

enum ReportFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Termly = 'termly';
    case Annually = 'annually';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Termly => 'Termly',
            self::Annually => 'Annually',
            self::Custom => 'Custom',
        };
    }

    /**
     * Return the reporting period bounds for the period containing $date.
     *
     * @return array{0: \Carbon\CarbonImmutable, 1: \Carbon\CarbonImmutable}
     */
    public function periodFor(\Carbon\CarbonInterface $date): array
    {
        $date = $date->toImmutable();

        return match ($this) {
            self::Weekly => [$date->startOfWeek(), $date->endOfWeek()],
            self::Monthly => [$date->startOfMonth(), $date->endOfMonth()],
            self::Termly, self::Custom => [$date->startOfQuarter(), $date->endOfQuarter()],
            self::Annually => [$date->startOfYear(), $date->endOfYear()],
        };
    }
}
