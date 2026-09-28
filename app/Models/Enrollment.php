<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch',
        'year_session_id',
        'term_id',
        'current_enrollment',
        'total_learners',
        'class_breakdown',
        'new_admissions',
        'departures',
        'comment',
    ];

    protected $casts = [
        'class_breakdown' => 'array',
    ];

    public const SECTIONS = ['EYE', 'Upper Primary', 'Junior School'];

    protected static function booted(): void
    {
        static::saving(function (Enrollment $enrollment): void {
            $classes = collect($enrollment->class_breakdown ?? [])
                ->map(function (array $class) use ($enrollment): array {
                    $class['boys'] = (int) ($class['boys'] ?? 0);
                    $class['girls'] = (int) ($class['girls'] ?? 0);
                    $class['total'] = $class['boys'] + $class['girls'];
                    $class['new_admissions'] = (int) ($class['new_admissions'] ?? 0);
                    $class['departures'] = (int) ($class['departures'] ?? 0);

                    if (empty($class['section'])) {
                        $class['section'] = static::resolveSection($enrollment->branch, $class['class_id'] ?? null);
                    }

                    return $class;
                });

            $enrollment->class_breakdown = $classes->all();
            $enrollment->total_learners = $classes->sum('total');
            $enrollment->current_enrollment = $enrollment->total_learners;
            $enrollment->new_admissions = $classes->sum('new_admissions');
            $enrollment->departures = $classes->sum('departures');
        });
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess()) {
            return $query;
        }

        return $query->where('branch', $user->branch);
    }

    /**
     * Distinct sections a class belongs to (via streams), in section order.
     *
     * @return array<int, string>
     */
    public static function sectionsForClass(?int $classId): array
    {
        if (! $classId) {
            return [];
        }

        $sections = Stream::query()
            ->where('class_id', $classId)
            ->distinct()
            ->pluck('section')
            ->filter()
            ->all();

        usort($sections, fn (string $a, string $b): int => array_search($a, static::SECTIONS) <=> array_search($b, static::SECTIONS));

        return array_values($sections);
    }

    /**
     * The section for a class in a branch, when it maps to exactly one.
     */
    public static function resolveSection(?string $branch, mixed $classId): ?string
    {
        if (! $classId) {
            return null;
        }

        $query = Stream::query()->where('class_id', (int) $classId);

        if ($branch) {
            $query->where('branch', $branch);
        }

        $sections = $query->distinct()->pluck('section')->filter()->unique()->values();

        return $sections->count() === 1 ? $sections->first() : null;
    }

    /**
     * Aggregate boys/girls/totals/admissions/departures per section plus overall.
     *
     * @param  iterable<int, Enrollment>  $enrollments
     * @return array{sections: array<string, array<string, int>>, overall: array<string, int>}
     */
    public static function sectionTotals(iterable $enrollments): array
    {
        $sections = [];

        $blank = fn (): array => [
            'boys' => 0, 'girls' => 0, 'total' => 0, 'admitted' => 0, 'left' => 0,
        ];

        foreach ($enrollments as $enrollment) {
            foreach ($enrollment->class_breakdown ?? [] as $class) {
                $section = $class['section']
                    ?? static::resolveSection($enrollment->branch, $class['class_id'] ?? null)
                    ?? 'Unassigned';

                $sections[$section] ??= $blank();

                $boys = (int) ($class['boys'] ?? 0);
                $girls = (int) ($class['girls'] ?? 0);

                $sections[$section]['boys'] += $boys;
                $sections[$section]['girls'] += $girls;
                $sections[$section]['total'] += (int) ($class['total'] ?? $boys + $girls);
                $sections[$section]['admitted'] += (int) ($class['new_admissions'] ?? 0);
                $sections[$section]['left'] += (int) ($class['departures'] ?? 0);
            }
        }

        $ordered = [];

        foreach (array_merge(static::SECTIONS, ['Unassigned']) as $name) {
            if (isset($sections[$name])) {
                $ordered[$name] = $sections[$name];
            }
        }

        $overall = $blank();

        foreach ($ordered as $totals) {
            foreach ($overall as $key => $value) {
                $overall[$key] = $value + $totals[$key];
            }
        }

        return ['sections' => $ordered, 'overall' => $overall];
    }

    public function getClassBreakdownSummaryAttribute(): string
    {
        return collect($this->class_breakdown ?? [])
            ->map(fn (array $class): string => sprintf(
                '%s: %d boys, %d girls, %d total, %d admitted, %d left',
                $class['class_name'] ?? $class['class_id'] ?? 'Class',
                $class['boys'] ?? 0,
                $class['girls'] ?? 0,
                $class['total'] ?? ((int) ($class['boys'] ?? 0) + (int) ($class['girls'] ?? 0)),
                $class['new_admissions'] ?? 0,
                $class['departures'] ?? 0,
            ))
            ->implode('; ');
    }

    public function yearSession(): BelongsTo
    {
        return $this->belongsTo(YearSession::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
