<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventTeam extends Model
{
    use HasFactory;

    protected $table = 'event_teams';

    protected $fillable = [
        'event_id',
        'event_branch_id',
        'event_group_id',
        'event_category_id',
        'team_name',
        'contingent',
        'branch_code',
        'branch_sequence',
        'participant_number',
    ];

    /* ================= RELATIONS ================= */

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class, 'event_team_id');
    }

    public function eventBranch()
    {
        return $this->belongsTo(EventBranch::class);
    }

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class);
    }

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class);
    }
}
