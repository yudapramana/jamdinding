<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventJudge extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    public function masterJudge()
    {
        return $this->belongsTo(MasterJudge::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function panelMembers()
    {
        return $this->hasMany(EventJudgePanelMember::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

   

}
