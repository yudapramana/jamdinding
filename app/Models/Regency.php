<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    protected $table = 'regencies';

    protected $fillable = ['province_id', 'name'];

    public $timestamps = false;

    /** Relasi ke Province */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /** Relasi ke District */
    public function districts()
    {
        return $this->hasMany(District::class);
    }
}
