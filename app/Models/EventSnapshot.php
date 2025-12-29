<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSnapshot extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'payload',
        'published_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
    ];
}
