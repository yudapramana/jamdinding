<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCompetitionGroup extends Model
{
    protected $table = 'master_competition_groups';

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
}
