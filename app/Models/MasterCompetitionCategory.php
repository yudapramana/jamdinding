<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCompetitionCategory extends Model
{
    protected $table = 'master_competition_categories';

    protected $fillable = [
        'code',
        'name',
        'order_number',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
