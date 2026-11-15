<?php

namespace App\Services;

use App\Models\SyllabusLesson;
use App\Models\SyllabusMapping;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SyllabusCoverage
{
    /**
     * @return Collection<int, SyllabusLesson>
     */
    public function lessons(SyllabusMapping $mapping, ?int $teacherId = null): Collection
    {
        $teacherId ??= $mapping->assignedTeacher()?->id;

        return SyllabusLesson::query()
            ->where('syllabus_mapping_id', $mapping->id)
            ->with(['substrand.strand', 'progress' => fn ($query) => $query->where('teacher_id', $teacherId)])
            ->orderBy('lesson_number')
            ->get();
    }

    /**
     * Overall coverage statistics for a mapping, mirroring the legacy Google Sheets logic.
     *
     * @return array{
     *     total_lessons: int,
     *     completed_lessons: int,
     *     pending_lessons: int,
     *     overdue_lessons: int,
     *     upcoming_lessons: int,
     *     not_scheduled_lessons: int,
     *     late_lessons: int,
     *     coverage_percent: float,
     *     highest_consecutive: int,
     *     completed_numbers: array<int, int>,
     *     gaps: array<int, int>,
     *     teacher_id: int|null,
     * }
     */
    public function stats(SyllabusMapping $mapping, ?int $teacherId = null): array
    {
        $teacherId ??= $mapping->assignedTeacher()?->id;

        $lessons = $this->lessons($mapping, $teacherId);
        $total = max((int) $mapping->total_lessons, $lessons->count());

        $completed = [];
        $overdue = 0;
        $upcoming = 0;
        $notScheduled = 0;
        $lateLessons = 0;

        $today = Carbon::today();

        foreach ($lessons as $lesson) {
            $progress = $lesson->progress->first();
            $expected = $lesson->substrand->strand->expected_completion_date;

            if ($progress?->completion_date) {
                $completed[] = $lesson->lesson_number;

                if ($expected && $progress->completion_date->gt($expected)) {
                    $lateLessons++;
                }

                continue;
            }

            if ($expected) {
                if ($expected->lt($today)) {
                    $overdue++;
                } else {
                    $upcoming++;
                }
            } else {
                $notScheduled++;
            }
        }

        sort($completed);
        $completedCount = count($completed);

        $consecutive = 0;
        $completedSet = array_flip($completed);

        for ($n = 1; $n <= $total; $n++) {
            if (isset($completedSet[$n])) {
                $consecutive = $n;
            } else {
                break;
            }
        }

        $maxCompleted = $completedCount ? max($completed) : 0;
        $gaps = [];

        for ($n = 1; $n <= $maxCompleted; $n++) {
            if (! isset($completedSet[$n])) {
                $gaps[] = $n;
            }
        }

        return [
            'total_lessons' => $total,
            'completed_lessons' => $completedCount,
            'pending_lessons' => max(0, $total - $completedCount),
            'overdue_lessons' => $overdue,
            'upcoming_lessons' => $upcoming,
            'not_scheduled_lessons' => $notScheduled,
            'late_lessons' => $lateLessons,
            'coverage_percent' => $total > 0 ? round(($consecutive / $total) * 100, 2) : 0.0,
            'highest_consecutive' => $consecutive,
            'completed_numbers' => $completed,
            'gaps' => $gaps,
            'teacher_id' => $teacherId,
        ];
    }

    /**
     * Per-lesson coverage percentage, per the legacy Google Sheets formula.
     */
    public function lessonCoverage(int $lessonNumber, int $totalLessons, bool $completed): float
    {
        if (! $completed || $totalLessons <= 0) {
            return 0.0;
        }

        return round(($lessonNumber / $totalLessons) * 100, 2);
    }

    /**
     * @return array<int, array{strand: \App\Models\SyllabusStrand, substrands: array<int, array{substrand: \App\Models\SyllabusSubstrand, lessons: Collection<int, SyllabusLesson>}>}>
     */
    public function groups(SyllabusMapping $mapping, ?int $teacherId = null): array
    {
        $lessons = $this->lessons($mapping, $teacherId);
        $groups = [];

        foreach ($lessons as $lesson) {
            $strand = $lesson->substrand->strand;
            $substrand = $lesson->substrand;

            $groups[$strand->id]['strand'] = $strand;
            $groups[$strand->id]['substrands'][$substrand->id]['substrand'] = $substrand;
            $groups[$strand->id]['substrands'][$substrand->id]['lessons'][] = $lesson;
        }

        return $groups;
    }

    /**
     * @param  Collection<int, SyllabusLesson>  $lessons
     * @return array<int, string>
     */
    public function assessmentDates($lessons): array
    {
        $lessonIds = $lessons->pluck('id')->all();

        if ($lessonIds === []) {
            return [];
        }

        $rows = \Illuminate\Support\Facades\DB::table('syllabus_assessment_lessons')
            ->join('syllabus_assessments', 'syllabus_assessments.id', '=', 'syllabus_assessment_lessons.syllabus_assessment_id')
            ->whereIn('syllabus_assessment_lessons.syllabus_lesson_id', $lessonIds)
            ->orderBy('syllabus_assessments.assessment_date')
            ->get(['syllabus_assessment_lessons.syllabus_lesson_id as lesson_id', 'syllabus_assessments.assessment_date']);

        $map = [];

        foreach ($rows as $row) {
            $map[$row->lesson_id][] = \Carbon\Carbon::parse($row->assessment_date)->format('d/m/Y');
        }

        return array_map(fn ($dates) => implode(', ', $dates), $map);
    }

    /**
     * @return array<int, string>
     */
    public function coordinatorComments(SyllabusMapping $mapping): array
    {
        $mapping->loadMissing('strands.reviews');

        return $mapping->strands
            ->mapWithKeys(fn ($strand) => [
                $strand->id => $strand->reviews->sortByDesc('reviewed_at')->first()?->comment,
            ])
            ->filter()
            ->all();
    }

    /**
     * Days a lesson was completed after its strand's expected date (positive = late,
     * zero = on time, negative = early), or null when not applicable.
     */
    public function lateDays(?\Carbon\CarbonInterface $expected, ?\Carbon\CarbonInterface $completion): ?int
    {
        if (! $expected || ! $completion) {
            return null;
        }

        return (int) $expected->copy()->startOfDay()->diffInDays($completion->copy()->startOfDay(), false);
    }

    /**
     * @return Collection<int, array{strand: \App\Models\SyllabusStrand, substrands: int, total: int, completed: int, pending: int, overdue: int, late_lessons: int, expected_completion_date: ?\Carbon\Carbon, coverage_percent: float}>
     */
    public function strandStats(SyllabusMapping $mapping, ?int $teacherId = null): Collection
    {
        $teacherId ??= $mapping->assignedTeacher()?->id;
        $total = max(1, (int) $mapping->total_lessons);
        $today = Carbon::today();

        $mapping->loadMissing('strands.substrands.lessons.progress');

        return $mapping->strands->map(function ($strand) use ($teacherId, $total, $today) {
            $lessons = $strand->substrands->flatMap->lessons;
            $expected = $strand->expected_completion_date;

            $completed = $lessons->filter(fn ($lesson) => $lesson->progress
                ->where('teacher_id', $teacherId)
                ->first()?->completion_date)->count();

            $overdue = $lessons->filter(function ($lesson) use ($teacherId, $expected, $today) {
                if (! $expected) {
                    return false;
                }

                $progress = $lesson->progress->where('teacher_id', $teacherId)->first();

                return ! $progress?->completion_date && $expected->lt($today);
            })->count();

            $lateLessons = $expected
                ? $lessons->filter(function ($lesson) use ($teacherId, $expected) {
                    $completion = $lesson->progress->where('teacher_id', $teacherId)->first()?->completion_date;

                    return $completion?->gt($expected);
                })->count()
                : 0;

            $strandTotal = $lessons->count();

            return [
                'strand' => $strand,
                'substrands' => $strand->substrands->count(),
                'total' => $strandTotal,
                'completed' => $completed,
                'pending' => $strandTotal - $completed,
                'overdue' => $overdue,
                'late_lessons' => $lateLessons,
                'expected_completion_date' => $expected,
                'coverage_percent' => $strandTotal > 0 ? round(($completed / $strandTotal) * 100, 2) : 0.0,
                'sequential_contribution' => round(($completed / $total) * 100, 2),
            ];
        })->values();
    }

    /**
     * Renumber every lesson in a mapping sequentially (1..N) following
     * strand / substrand / lesson sort order, and refresh total_lessons.
     */
    public function renumber(SyllabusMapping $mapping): int
    {
        $mapping->load('strands.substrands.lessons');

        $number = 0;

        foreach ($mapping->strands as $strand) {
            $strand->substrands->each(function ($substrand, $index) use (&$number, $mapping) {
                $substrand->updateQuietly(['sort_order' => $index + 1]);

                foreach ($substrand->lessons as $lessonIndex => $lesson) {
                    $number++;
                    $lesson->updateQuietly([
                        'syllabus_mapping_id' => $mapping->id,
                        'lesson_number' => $number,
                        'sort_order' => $lessonIndex + 1,
                    ]);
                }
            });

            $strand->updateQuietly(['sort_order' => $mapping->strands->search($strand) + 1]);
        }

        $mapping->updateQuietly(['total_lessons' => $number]);

        return $number;
    }
}
