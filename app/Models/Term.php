<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_session_id',
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

    public function yearSession(): BelongsTo
    {
        return $this->belongsTo(YearSession::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(Week::class)->orderBy('number')->orderBy('start_date')->orderBy('id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
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

    protected static function booted(): void
    {
        static::saving(function (Term $term): void {
            if ($term->status === 'active') {
                static::query()
                    ->where('year_session_id', $term->year_session_id)
                    ->whereKeyNot($term->getKey())
                    ->where('status', 'active')
                    ->update(['status' => 'completed']);
            }
        });
    }
}
