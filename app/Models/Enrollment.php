<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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

    protected static function booted(): void
    {
        static::saving(function (Enrollment $enrollment): void {
            $classes = collect($enrollment->class_breakdown ?? [])
                ->map(function (array $class): array {
                    $class['boys'] = (int) ($class['boys'] ?? 0);
                    $class['girls'] = (int) ($class['girls'] ?? 0);
                    $class['total'] = $class['boys'] + $class['girls'];
                    $class['new_admissions'] = (int) ($class['new_admissions'] ?? 0);
                    $class['departures'] = (int) ($class['departures'] ?? 0);

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
