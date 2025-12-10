<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventGroupJudge extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_chief' => 'boolean',
    ];

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
