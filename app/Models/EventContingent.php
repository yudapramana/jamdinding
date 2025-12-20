<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventContingent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'total_participant' => 'integer',
        'gold_count' => 'integer',
        'silver_count' => 'integer',
        'bronze_count' => 'integer',
        'fourth_count' => 'integer',
        'total_point' => 'integer',
    ];


    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
