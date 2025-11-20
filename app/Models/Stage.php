<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stage extends Model
{
    use HasFactory;

    protected $table = 'stages';

    protected $fillable = [
        'name',
        'order_number',
        'description',
        'is_active',
        'days'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Tahapan ini bisa diadopsi banyak event
    public function eventStages()
    {
        return $this->hasMany(EventStage::class, 'stage_id');
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
