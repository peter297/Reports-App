<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'syllabus_mapping_id',
        'syllabus_substrand_id',
        'lesson_number',
        'topic',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'lesson_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(SyllabusMapping::class, 'syllabus_mapping_id');
    }

    public function substrand(): BelongsTo
    {
        return $this->belongsTo(SyllabusSubstrand::class, 'syllabus_substrand_id');
    }

    public function strand(): ?SyllabusStrand
    {
        return $this->substrand?->strand;
    }

    public function progress(): HasMany
    {
        return $this->hasMany(SyllabusLessonProgress::class);
    }

    public function progressFor(?int $teacherId): ?SyllabusLessonProgress
    {
        if (! $teacherId) {
            return null;
        }

        return $this->progress->firstWhere('teacher_id', $teacherId);
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(SyllabusAssessment::class, 'syllabus_assessment_lessons')->withTimestamps();
    }
}
