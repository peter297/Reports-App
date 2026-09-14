<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'event_date', 'status', 'branch','is_active',
        'in_charge', 'term_id','event_week'
    ];



    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'class_event', 'event_id', 'class_id');
    }

    public function week()
    {
        return $this->belongsTo(Week::class, 'event_week');
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id'); // Ensure 'term_id' matches your column name
    }



}
