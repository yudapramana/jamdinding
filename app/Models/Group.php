<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_team' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function masterGroups()
    {
        return $this->hasMany(MasterGroup::class);
    }

    public function masterCategories()
    {
        return $this->hasMany(MasterCategory::class);
    }

    public function eventGroups()
    {
        return $this->hasMany(EventGroup::class);
    }

    public function eventCategories()
    {
        return $this->hasMany(EventCategory::class);
    }
}
