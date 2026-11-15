<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusStrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'syllabus_mapping_id',
        'name',
        'description',
        'expected_completion_date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'expected_completion_date' => 'date',
        ];
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(SyllabusMapping::class, 'syllabus_mapping_id');
    }

    public function substrands(): HasMany
    {
        return $this->hasMany(SyllabusSubstrand::class)->orderBy('sort_order')->orderBy('id');
    }

    public function latestReview(): ?SyllabusCoordinatorReview
    {
        return $this->reviews()->latest('reviewed_at')->first();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SyllabusCoordinatorReview::class)->orderByDesc('reviewed_at');
    }
}
