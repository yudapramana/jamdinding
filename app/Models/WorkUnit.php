<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkUnit extends Model 
{

    protected $table = 'work_units';
    public $timestamps = true;

    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $guarded = [];

    public function employee()
    {
        return $this->hasMany('App\Models\Employee');
    }

    public function parent()
    {
        return $this->belongsTo(WorkUnit::class, 'parent_unit');
    }

    public function children()
    {
        return $this->hasMany(WorkUnit::class, 'parent_unit');
    }

}