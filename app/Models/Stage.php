<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'days' => 'integer',
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function eventStages()
    {
        return $this->hasMany(EventStage::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_stages')
            ->using(EventStage::class)
            ->withPivot(['order_number', 'name', 'start_date', 'end_date', 'notes', 'is_active'])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | LOCAL SCOPES
    |--------------------------------------------------------------------------
    */

    // Urutkan berdasarkan nomor urutan
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
