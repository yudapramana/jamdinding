<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventFieldComponent extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class);
    }

    public function field()
    {
        return $this->belongsTo(ListField::class, 'field_id');
    }
}
