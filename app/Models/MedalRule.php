<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedalRule extends Model
{
    use HasFactory;

    protected $table = 'medal_rules';

    protected $fillable = [
        'order_number',
        'medal_code',
        'medal_name',
        'point',
        'is_active',
    ];

    /* =====================
     |  Relationships
     ===================== */

    public function medalStandings()
    {
        return $this->hasMany(MedalStanding::class);
    }
}
