<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch',
        'section',
        'class_id',
        'stream_id',
        'total_boys',
        'total_girls',
        'class_total',
    ];

    protected function casts(): array
    {
        return [
            'total_boys' => 'integer',
            'total_girls' => 'integer',
            'class_total' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ClassSize $size): void {
            $size->total_boys = max(0, (int) $size->total_boys);
            $size->total_girls = max(0, (int) $size->total_girls);
            $size->class_total = $size->total_boys + $size->total_girls;
        });
    }

    /**
     * @return Builder<static>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess()) {
            return $query;
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

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }
}
