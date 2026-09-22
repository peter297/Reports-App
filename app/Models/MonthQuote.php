<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
        ];
    }

    public function getMonthNameAttribute(): string
    {
        return \Carbon\Carbon::create(null, $this->month, 1)->format('F');
    }
}
