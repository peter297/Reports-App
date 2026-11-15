<?php

namespace App\Enums;

enum SchoolSection: string
{
    case EarlyYearsEducation = 'Early Years Education';
    case UpperPrimary = 'Upper Primary';
    case JuniorSchool = 'Junior School';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value'),
        );
    }
}
