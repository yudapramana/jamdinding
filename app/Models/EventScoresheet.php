<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventScoresheet extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'total_score' => 'float',
        'rank_in_round' => 'integer',
    ];

    public function competition()
    {
        return $this->belongsTo(EventCompetition::class, 'event_competition_id');
    }

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class);
    }

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class);
    }

    public function eventParticipant()
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function judge()
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function items()
    {
        return $this->hasMany(EventScoreItem::class, 'event_scoresheet_id');
    }
}
