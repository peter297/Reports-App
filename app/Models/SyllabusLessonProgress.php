<?php

namespace App\Models;

use App\Enums\SyllabusLessonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusLessonProgress extends Model
{
    use HasFactory;

    protected $table = 'syllabus_lesson_progress';

    protected $fillable = [
        'syllabus_lesson_id',
        'teacher_id',
        'updated_by',
        'completion_date',
        'teacher_comment',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(SyllabusLesson::class, 'syllabus_lesson_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function status(?\Carbon\CarbonInterface $expected = null): SyllabusLessonStatus
    {
        return SyllabusLessonStatus::fromProgress(
            $this->completion_date?->toDateString(),
            $expected?->toDateString(),
        );
    }

    /**
     * Coverage contribution of this lesson, mirroring the legacy Google Sheets formula:
     * completed lessons earn (lesson_number / total_lessons) * 100.
     */
    public function coveragePercent(int $totalLessons): float
    {
        if (! $this->completion_date || $totalLessons <= 0) {
            return 0.0;
        }

        return round(($this->lesson->lesson_number / $totalLessons) * 100, 2);
    }
}
