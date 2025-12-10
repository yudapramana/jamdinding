<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventBranch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'order_number' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
