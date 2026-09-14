<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_session_id',
        'term_id',
        'week_id',
        'branch',
        'section',
        'attendance_date',
        'class_id',
        'stream_id',
        'total_boys',
        'total_girls',
        'class_total',
        'total_present',
        'total_absent',
        'percentage_present',
        'percentage_absent',
    ];

    protected $table = 'attendances';

    protected $casts = [
        'attendance_date' => 'date',
        'percentage_present' => 'decimal:2',
        'percentage_absent' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Attendance $attendance): void {
            $attendance->total_boys = (int) $attendance->total_boys;
            $attendance->total_girls = (int) $attendance->total_girls;
            $attendance->class_total = $attendance->total_boys + $attendance->total_girls;
            $attendance->total_present = (int) $attendance->total_present;
            $attendance->total_absent = max(0, $attendance->class_total - $attendance->total_present);
            $attendance->percentage_present = $attendance->class_total > 0
                ? round(($attendance->total_present / $attendance->class_total) * 100, 2)
                : 0;
            $attendance->percentage_absent = round(100 - $attendance->percentage_present, 2);
        });
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            foreach ($user->sectionCoordinatorAssignments as $assignment) {
                $query->orWhere(function (Builder $query) use ($assignment): void {
                    $query->where('branch', $assignment->branch)
                        ->where('section', $assignment->section);
                });
            }
        });
    }

    public function getWeeklyAveragePercentageAttribute(): float
    {
        return round((float) static::query()
            ->where('year_session_id', $this->year_session_id)
            ->where('term_id', $this->term_id)
            ->where('week_id', $this->week_id)
            ->where('branch', $this->branch)
            ->where('section', $this->section)
            ->avg('percentage_present'), 2);
    }

    public function yearSession(): BelongsTo
    {
        return $this->belongsTo(YearSession::class);
    }

    /**
     * Get the term that owns the attendance.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Get the week that owns the attendance.
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function streams()
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }
    

    
}
