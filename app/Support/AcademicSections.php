<?php

namespace App\Support;

use App\Models\AcademicLevel;

/**
 * Bidirectional mapping between the legacy attendance "section" values
 * ('EYE', 'Upper Primary', 'Junior School') and the academic_levels table
 * ('Early Years Education', 'Upper Primary', 'Junior School').
 */
class AcademicSections
{
    /** @var array<string, string> canonical level name => legacy aliases */
    private const ALIASES = [
        'Early Years Education' => ['EYE', 'Early Years - EYE', 'Early Years Education'],
        'Upper Primary' => ['Upper Primary'],
        'Junior School' => ['Junior School'],
    ];

    /** Normalize a section value to the canonical academic level name. */
    public static function canonical(?string $section): ?string
    {
        if (blank($section)) {
            return null;
        }

        foreach (self::ALIASES as $level => $aliases) {
            if (in_array(trim($section), $aliases, true)) {
                return $level;
            }
        }

        // Unknown values pass through unchanged (case-insensitive compare to level names).
        foreach (self::ALIASES as $level => $aliases) {
            if (strcasecmp(trim($section), $level) === 0) {
                return $level;
            }
        }

        return trim($section);
    }

    /** All alias values (including the level name itself) for a level name. */
    public static function aliases(string $levelName): array
    {
        $levelName = self::canonical($levelName) ?? $levelName;

        return self::ALIASES[$levelName] ?? [$levelName];
    }

    /** The canonical code stored in attendances/class_sizes `section` columns. */
    public static function codeFor(?string $levelName): ?string
    {
        $canonical = self::canonical($levelName);

        if ($canonical === null) {
            return null;
        }

        return self::ALIASES[$canonical][0] ?? $canonical;
    }

    /** Display label for a section code. */
    public static function labelFor(string $code): string
    {
        return match ($code) {
            'EYE' => 'EYE - Early Years Education',
            default => $code,
        };
    }

    /** Resolve the academic_levels.id for a section value, or null when unknown. */
    public static function levelId(?string $section): ?int
    {
        $canonical = self::canonical($section);

        if ($canonical === null) {
            return null;
        }

        return AcademicLevel::where('name', $canonical)->value('id');
    }
}
