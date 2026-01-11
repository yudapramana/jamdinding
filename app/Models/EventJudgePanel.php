<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventJudgePanel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function members()
    {
        return $this->hasMany(EventJudgePanelMember::class);
    }

    public static function nextNumberForEvent(int $eventId): int
    {
        return self::where('event_id', $eventId)->count() + 1;
    }

    public function eventGroups()
    {
        return $this->hasMany(EventGroupJudgePanel::class);
    }

    public function location()
    {
        return $this->belongsTo(EventLocation::class, 'event_location_id');
    }


}
