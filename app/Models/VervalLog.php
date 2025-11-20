<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VervalLog extends Model 
{

    protected $table = 'verval_logs';
    public $timestamps = true;
    protected $guarded = [];


    public function document()
    {
        return $this->belongsTo('App\Models\EmpDocument', 'id_document', 'id');
    }

    public function verifier()
    {
        return $this->belongsTo('App\Models\User', 'verified_by', 'id');
    }

}