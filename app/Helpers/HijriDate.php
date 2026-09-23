<?php

namespace App\Helpers;

/**
 * Pure-PHP Gregorian to Hijri (Islamic civil calendar) conversion using the
 * Kuwaiti algorithm. Accurate to within a day of the observed calendar.
 */
final class HijriDate
{
    public const MONTHS = [
        1 => 'Muharram',
        2 => 'Safar',
        3 => 'Rabi al-Awwal',
        4 => 'Rabi al-Thani',
        5 => 'Jumada al-Ula',
        6 => 'Jumada al-Akhirah',
        7 => 'Rajab',
        8 => "Sha'ban",
        9 => 'Ramadan',
        10 => 'Shawwal',
        11 => "Dhu al-Qa'dah",
        12 => 'Dhu al-Hijjah',
    ];

    /**
     * @return array{int, int, int} [year, month, day]
     */
    public static function fromGregorian(int $year, int $month, int $day): array
    {
        $y = $year;
        $m = $month;

        if ($m < 3) {
            $y -= 1;
            $m += 12;
        }

        $a = (int) floor($y / 100);
        $b = 2 - $a + (int) floor($a / 4);
        $jd = (int) (floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $day + $b - 1524);

        $l = $jd - 1948440 + 10632;
        $n = (int) floor(($l - 1) / 10631);
        $l = $l - 10631 * $n + 354;
        $j = (int) (floor((10985 - $l) / 5316) * floor((50 * $l) / 17719)
            + floor($l / 5670) * floor((43 * $l) / 15238));
        $l = $l - (int) (floor((30 - $j) / 15) * floor((17719 * $j) / 50))
            - (int) (floor($j / 16) * floor((15238 * $j) / 43)) + 29;

        $hm = (int) floor((24 * $l) / 709);
        $hd = $l - (int) floor((709 * $hm) / 24);
        $hy = 30 * $n + $j - 30;

        return [$hy, $hm, $hd];
    }

    /**
     * Label like "RAJAB 1447" or "RAJAB - SHA'BAN 1447" for a Gregorian month.
     */
    public static function monthLabel(int $year, int $month): string
    {
        $start = \Carbon\Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        [$startYear, $startMonth] = self::fromGregorian($year, $month, 1);
        [$endYear, $endMonth] = self::fromGregorian($year, $month, $end->day);

        $startName = strtoupper(self::MONTHS[$startMonth]);
        $endName = strtoupper(self::MONTHS[$endMonth]);

        if ($startMonth === $endMonth && $startYear === $endYear) {
            return "{$startName} {$startYear}";
        }

        return "{$startName} - {$endName} {$endYear}";
    }
}
