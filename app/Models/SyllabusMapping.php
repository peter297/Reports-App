<?php

namespace App\Models;

use App\Enums\SyllabusMappingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_session_id',
        'term_id',
        'class_id',
        'stream_id',
        'subject_id',
        'total_lessons',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SyllabusMappingStatus::class,
            'total_lessons' => 'integer',
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
     * Branch name derived from the class (the mapping has no branch column of its own).
     */
    protected function getBranchAttribute(): ?string
    {
        return $this->class?->branch?->name;
    }

    /**
     * Display label combining the class and stream, e.g. "Grade 1 Blue".
     */
    protected function getGradeStreamAttribute(): string
    {
        $name = $this->class?->name ?? '—';
        $stream = $this->stream?->name;

        return $stream ? "{$name} {$stream}" : $name;
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function strands(): HasMany
    {
        return $this->hasMany(SyllabusStrand::class)->orderBy('sort_order')->orderBy('id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(SyllabusLesson::class)->orderBy('lesson_number');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(SyllabusAssessment::class)->orderByDesc('assessment_date');
    }

    /**
     * Mappings the given user is allowed to see, resolved through the
     * organizational scope resolver (single source of truth).
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return \App\Services\ReportScope::for($user)->applyTo($query);
    }

    /**
     * @param  array{year_session_id: int, term_id: int, class_id: int, stream_id: int|null, subject_id: int}|SyllabusMapping  $attributes
     */
    public function duplicateExists(?int $ignoreId = null): bool
    {
        return static::query()
            ->where('year_session_id', $this->year_session_id)
            ->where('term_id', $this->term_id)
            ->where('class_id', $this->class_id)
            ->where(function (Builder $query): void {
                $this->stream_id
                    ? $query->where('stream_id', $this->stream_id)
                    : $query->whereNull('stream_id');
            })
            ->where('subject_id', $this->subject_id)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->whereIn('status', [SyllabusMappingStatus::Draft->value, SyllabusMappingStatus::Active->value])
            ->exists();
    }

    /**
     * The teacher primarily responsible for this mapping (first assignment for its session/class/stream/subject).
     */
    public function assignedTeacher(): ?User
    {
        $assignment = SubjectAssignment::query()
            ->where('year_session_id', $this->year_session_id)
            ->where('subject_id', $this->subject_id)
            ->where('class_id', $this->class_id)
            ->when($this->stream_id, fn ($query) => $query->where('stream_id', $this->stream_id))
            ->where('assignment_role', SubjectAssignment::ROLE_TEACHER)
            ->with('user')
            ->oldest('id')
            ->first();

        return $assignment?->user;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function assignedTeachers(): \Illuminate\Support\Collection
    {
        return SubjectAssignment::query()
            ->where('year_session_id', $this->year_session_id)
            ->where('subject_id', $this->subject_id)
            ->where('class_id', $this->class_id)
            ->when($this->stream_id, fn ($query) => $query->where('stream_id', $this->stream_id))
            ->where('assignment_role', SubjectAssignment::ROLE_TEACHER)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->values();
    }
}
