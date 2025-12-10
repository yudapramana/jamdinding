<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventScoreItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'score' => 'float',
        'max_score' => 'float',
        'weight' => 'integer',
        'weighted_score' => 'float',
    ];

    public function scoresheet()
    {
        return $this->belongsTo(EventScoresheet::class, 'event_scoresheet_id');
    }
}
