<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_team' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function categories()
    {
        return $this->hasMany(EventCategory::class);
    }

    public function fieldComponents()
    {
        return $this->hasMany(EventFieldComponent::class);
    }

    public function competitions()
    {
        return $this->hasMany(EventCompetition::class);
    }

    public function judges()
    {
        return $this->hasMany(EventGroupJudge::class);
    }

    public function scoresheets()
    {
        return $this->hasMany(EventScoresheet::class);
    }
}
