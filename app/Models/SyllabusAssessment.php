<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SyllabusAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'syllabus_mapping_id',
        'assessment_date',
        'assessment_type',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
        ];
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(SyllabusMapping::class, 'syllabus_mapping_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(SyllabusLesson::class, 'syllabus_assessment_lessons')->withTimestamps();
    }
}
