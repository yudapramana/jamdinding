<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function masterBranches()
    {
        return $this->hasMany(MasterBranch::class);
    }

    public function masterGroups()
    {
        return $this->hasMany(MasterGroup::class);
    }

    public function masterCategories()
    {
        return $this->hasMany(MasterCategory::class);
    }

    public function eventBranches()
    {
        return $this->hasMany(EventBranch::class);
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
