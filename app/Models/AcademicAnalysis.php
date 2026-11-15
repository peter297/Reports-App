<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicAnalysis extends Model
{
    use HasFactory;

    public const EXAM_TYPES = ['Mid Term', 'End Term'];

    protected $fillable = [
        'branch',
        'section',
        'year_session_id',
        'term_id',
        'exam_type',
        'class_id',
        'stream_id',
        'subjects',
    ];

    protected function casts(): array
    {
        return [
            'subjects' => 'array',
        ];
    }

    public function yearSession(): BelongsTo
    {
        return $this->belongsTo(YearSession::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    /**
     * Per-subject stats with band percentages and success rate.
     *
     * @return array<int, array<string, mixed>>
     */
    public function subjectStats(): array
    {
        return collect($this->subjects ?? [])
            ->map(function (array $row): array {
                $average = (float) ($row['average'] ?? 0);
                $ee = (int) ($row['exceeding'] ?? 0);
                $me = (int) ($row['meeting'] ?? 0);
                $ae = (int) ($row['approaching'] ?? 0);
                $be = (int) ($row['below'] ?? 0);
                $total = $ee + $me + $ae + $be;

                return [
                    'subject' => (string) ($row['subject'] ?? ''),
                    'average' => $average,
                    'exceeding' => $ee,
                    'exceeding_pct' => $total > 0 ? round($ee / $total * 100, 2) : 0,
                    'meeting' => $me,
                    'meeting_pct' => $total > 0 ? round($me / $total * 100, 2) : 0,
                    'approaching' => $ae,
                    'approaching_pct' => $total > 0 ? round($ae / $total * 100, 2) : 0,
                    'below' => $be,
                    'below_pct' => $total > 0 ? round($be / $total * 100, 2) : 0,
                    'total' => $total,
                    'success_rate' => $total > 0 ? round(($ee + $me) / $total * 100, 2) : 0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Column means across subjects, like the AVERAGES row.
     *
     * Success rate follows (total exceeding + total meeting) / total learners * 100.
     *
     * @return array<string, float>
     */
    public function averagesRow(): array
    {
        $stats = $this->subjectStats();
        $count = count($stats);

        if ($count === 0) {
            return [
                'average' => 0, 'exceeding' => 0, 'exceeding_pct' => 0,
                'meeting' => 0, 'meeting_pct' => 0, 'approaching' => 0,
                'approaching_pct' => 0, 'below' => 0, 'below_pct' => 0,
                'total' => 0, 'success_rate' => 0,
            ];
        }

        $mean = fn (string $key): float => round(collect($stats)->avg($key), 2);

        $eeSum = collect($stats)->sum('exceeding');
        $meSum = collect($stats)->sum('meeting');
        $totalSum = collect($stats)->sum('total');

        $row = [
            'average' => $mean('average'),
            'exceeding' => $mean('exceeding'),
            'exceeding_pct' => $mean('exceeding_pct'),
            'meeting' => $mean('meeting'),
            'meeting_pct' => $mean('meeting_pct'),
            'approaching' => $mean('approaching'),
            'approaching_pct' => $mean('approaching_pct'),
            'below' => $mean('below'),
            'below_pct' => $mean('below_pct'),
            'total' => $mean('total'),
            'success_rate' => 0,
        ];

        $row['success_rate'] = $totalSum > 0
            ? round(($eeSum + $meSum) / $totalSum * 100, 2)
            : 0;

        return $row;
    }

    public function overallAverage(): float
    {
        return $this->averagesRow()['average'];
    }

    /**
     * Mean of subject averages across a set of analyses, per subject.
     *
     * @param  iterable<int, AcademicAnalysis>  $analyses
     * @return array<int, array<string, mixed>>
     */
    public static function compiledSubjectMeans(iterable $analyses): array
    {
        $bySubject = [];

        foreach ($analyses as $analysis) {
            foreach ($analysis->subjectStats() as $stat) {
                $bySubject[$stat['subject']][] = $stat;
            }
        }

        $compiled = [];

        foreach ($bySubject as $subject => $stats) {
            $count = count($stats);
            $mean = fn (string $key): float => $count > 0 ? round(collect($stats)->avg($key), 2) : 0;

            $eeSum = collect($stats)->sum('exceeding');
            $meSum = collect($stats)->sum('meeting');
            $totalSum = collect($stats)->sum('total');

            $row = [
                'subject' => $subject,
                'average' => $mean('average'),
                'exceeding' => $mean('exceeding'),
                'exceeding_pct' => $mean('exceeding_pct'),
                'meeting' => $mean('meeting'),
                'meeting_pct' => $mean('meeting_pct'),
                'approaching' => $mean('approaching'),
                'approaching_pct' => $mean('approaching_pct'),
                'below' => $mean('below'),
                'below_pct' => $mean('below_pct'),
                'total' => $mean('total'),
                'success_rate' => $totalSum > 0 ? round(($eeSum + $meSum) / $totalSum * 100, 2) : 0,
                'entries' => $count,
            ];

            $compiled[] = $row;
        }

        return $compiled;
    }

    /**
     * Aggregate success rate: (total exceeding + total meeting) / total learners * 100.
     *
     * @param  iterable<int, AcademicAnalysis>  $analyses
     */
    public static function aggregateSuccessRate(iterable $analyses): float
    {
        $ee = 0;
        $me = 0;
        $total = 0;

        foreach ($analyses as $analysis) {
            foreach ($analysis->subjectStats() as $stat) {
                $ee += $stat['exceeding'];
                $me += $stat['meeting'];
                $total += $stat['total'];
            }
        }

        return $total > 0 ? round(($ee + $me) / $total * 100, 2) : 0;
    }

    public static function meanOfAverages(iterable $analyses): float
    {
        $values = [];

        foreach ($analyses as $analysis) {
            $values[] = $analysis->overallAverage();
        }

        return count($values) > 0 ? round(array_sum($values) / count($values), 2) : 0;
    }

    /**
     * @return Builder<static>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess() || $user->isHead()) {
            return $query;
        }

        if ($user->isDeputy()) {
            return $query->where('branch', $user->branch);
        }

        $assignments = $user->sectionCoordinatorAssignments;

        if ($assignments->isEmpty()) {
            return $query->whereKey(0);
        }

        return $query->where(function (Builder $query) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $query->orWhere(function (Builder $query) use ($assignment): void {
                    $query->where('branch', $assignment->branch)
                        ->where('section', $assignment->section);
                });
            }
        });
    }

    public function scopeForTerm(Builder $query, int $yearSessionId, int $termId, ?string $examType = null): Builder
    {
        return $query->where('year_session_id', $yearSessionId)
            ->where('term_id', $termId)
            ->when($examType, fn (Builder $query): Builder => $query->where('exam_type', $examType));
    }
}
