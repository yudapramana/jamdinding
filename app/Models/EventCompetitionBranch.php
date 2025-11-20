<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCompetitionBranch extends Model
{
    protected $table = 'event_competition_branches';

    protected $fillable = [
        'event_id',
        'master_competition_branch_id',
        'code',
        'master_competition_group_id',
        'master_competition_category_id',
        'type',
        'format',
        'name',
        'max_age',
        'order_number',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_age'   => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function masterBranch()
    {
        return $this->belongsTo(MasterCompetitionBranch::class, 'master_competition_branch_id');
    }

    public function group()
    {
        return $this->belongsTo(MasterCompetitionGroup::class, 'master_competition_group_id');
    }

    public function category()
    {
        return $this->belongsTo(MasterCompetitionCategory::class, 'master_competition_category_id');
    }
}
