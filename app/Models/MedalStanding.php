<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedalStanding extends Model
{
    use HasFactory;

    protected $table = 'medal_standings';

    protected $guarded = [];

    /* =====================
     |  Relationships
     ===================== */

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class);
    }

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class);
    }

    public function medalRule()
    {
        return $this->belongsTo(MedalRule::class);
    }

    public function eventMedalRule()
    {
        return $this->belongsTo(EventMedalRule::class);
    }

    public function region()
    {
        return $this->morphTo(null, 'region_type', 'region_id');
    }


    /* =====================
     |  Accessor (Optional)
     ===================== */

    /**
     * Ambil rule aktif (event override > default)
     */
    public function getActiveMedalRuleAttribute()
    {
        return $this->event_medal_rule_id
            ? $this->eventMedalRule
            : $this->medalRule;
    }
}
