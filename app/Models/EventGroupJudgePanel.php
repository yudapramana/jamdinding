<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventGroupJudgePanel extends Model
{
    use HasFactory;

    protected $guarded = [];

    /* =====================
     * RELATIONS
     * ===================== */

    public function panelMember()
    {
        return $this->belongsTo(
            EventJudgePanelMember::class,
            'event_judge_panel_member_id'
        );
    }

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class);
    }
}
