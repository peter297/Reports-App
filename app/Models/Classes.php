<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classes extends Model
{
    use HasFactory;

    protected $fillable =[
        'name',

    ];

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'class_event', 'class_id', 'event_id');
    }


}
