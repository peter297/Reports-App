<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
<<<<<<< HEAD
        'code',
        'academic_level_id',
        'is_active',
=======
        'section',
        'branch',
        'sort_order',
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
    ];

    protected function casts(): array
    {
        return [
<<<<<<< HEAD
            'is_active' => 'boolean',
        ];
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'academic_level_id');
    }

    public function syllabusMappings(): HasMany
    {
        return $this->hasMany(SyllabusMapping::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
=======
            'sort_order' => 'integer',
        ];
    }

    /**
     * Master subject names for a section (branch-specific first, then generic).
     *
     * @return array<int, string>
     */
    public static function namesForSection(string $section, ?string $branch = null): array
    {
        $query = static::query()->where('section', $section)->orderBy('sort_order')->orderBy('name');

        if ($branch) {
            $names = (clone $query)->where('branch', $branch)->pluck('name')->all();

            if ($names !== []) {
                return $names;
            }
        }

        return $query->whereNull('branch')->pluck('name')->all();
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
    }
}
