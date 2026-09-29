<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'section',
        'branch',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
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
    }
}
