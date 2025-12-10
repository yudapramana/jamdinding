<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListField extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function masterFieldComponents()
    {
        return $this->hasMany(MasterFieldComponent::class, 'field_id');
    }

    public function eventFieldComponents()
    {
        return $this->hasMany(EventFieldComponent::class, 'field_id');
    }
}
