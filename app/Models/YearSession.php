<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YearSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class)->orderBy('start_date')->orderBy('id');
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    public function syllabusMappings(): HasMany
    {
        return $this->hasMany(SyllabusMapping::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function active(): ?static
    {
        return static::query()->active()->latest('id')->first();
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('start_date')->orderByDesc('id');
    }

    protected static function booted(): void
    {
        static::saving(function (YearSession $session): void {
            if ($session->status === 'active') {
                static::query()
                    ->whereKeyNot($session->getKey())
                    ->where('status', 'active')
                    ->update(['status' => 'archived']);
            }
        });
    }
}
