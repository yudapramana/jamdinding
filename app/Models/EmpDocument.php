<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;


class EmpDocument extends Model 
{
    use SoftDeletes;

    protected $table = 'emp_documents';
    public $timestamps = true;
    
    protected $dates = ['deleted_at'];

    protected $guarded = [];

    protected $appends = ['file_url'];

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee', 'id_employee', 'id');
    }

    public function doctype()
    {
        return $this->belongsTo(DocType::class, 'id_doc_type');
    }

    public function vervallog()
    {
        return $this->hasMany(VervalLog::class, 'id_document');
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? url('secure/'.$this->file_path) : null;
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

}