<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventJudgePanelMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_chief' => 'boolean',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    public function panel()
    {
        return $this->belongsTo(
            EventJudgePanel::class,
            'event_judge_panel_id'
        );
    }

    public function eventJudge()
    {
        return $this->belongsTo(EventJudge::class);
    }

    public function groupPanels()
    {
        return $this->hasMany(EventGroupJudgePanel::class);
    }
}
