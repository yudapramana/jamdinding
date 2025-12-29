<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventMedalRule extends Model
{
    use HasFactory;

    protected $table = 'event_medal_rules';

    protected $fillable = [
        'event_id',
        'order_number',
        'medal_code',
        'medal_name',
        'point',
        'is_active',
    ];

    /* =====================
     |  Relationships
     ===================== */

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function medalStandings()
    {
        return $this->hasMany(MedalStanding::class);
    }
}
