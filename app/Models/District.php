<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';

    protected $fillable = ['regency_id', 'name'];

    public $timestamps = false;

    /** Relasi ke Regency */
    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }

    /** Relasi ke Village */
    public function villages()
    {
        return $this->hasMany(Village::class);
    }
}
