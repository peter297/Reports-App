<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    protected $fillable =[
        'department_id',
        'first_name',
        'middle_name',
        'last_name',
        'address',
        'zip_code',
        'date_of_birth',
        'date_hired',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
