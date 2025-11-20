<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date'  => 'date:Y-m-d',
    ];

    protected $appends = ['uid', 'uname', 'uemail'];

    // protected $with = ['works'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUidAttribute(){
        return $this->user->id;
    }

    public function getUnameAttribute(){
        return $this->user->name;
    }

    public function getUemailAttribute(){
        return $this->user->email;
    }

    public function works(){
        return $this->hasMany(Work::class, 'report_id', 'id');
    }
}
