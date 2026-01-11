<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterJudge extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function eventJudges()
    {
        return $this->hasMany(EventJudge::class);
    }
}
