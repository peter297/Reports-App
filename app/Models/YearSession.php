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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function active(): ?self
    {
        return static::query()->active()->latest('id')->first();
    }

    protected static function booted(): void
    {
        static::saving(function (YearSession $session): void {
            if ($session->is_active) {
                static::query()
                    ->whereKeyNot($session->getKey())
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }
}
