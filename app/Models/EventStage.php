<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventStage extends Model
{
    use HasFactory;

    protected $table = 'event_stages';

    protected $fillable = [
        'event_id',
        'stage_id',
        'order_number',
        'name',
        'start_date',
        'end_date',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',  // tanpa timezone shift
        'end_date'   => 'date:Y-m-d',
        'is_active'  => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Relasi ke event induknya
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Relasi ke master stage
    public function masterStage()
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    // Gabungkan tanggal dalam 1 string
    public function getDateRangeAttribute()
    {
        if (!$this->start_date || !$this->end_date) return null;

        return $this->start_date->format('d M Y') . ' – ' . $this->end_date->format('d M Y');
    }

    /*
    |--------------------------------------------------------------------------
    | LOCAL SCOPES
    |--------------------------------------------------------------------------
    */

    // Untuk sorting urutan tahapan
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
