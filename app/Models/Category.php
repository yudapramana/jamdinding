<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function masterCategories()
    {
        return $this->hasMany(MasterCategory::class);
    }

    public function eventCategories()
    {
        return $this->hasMany(EventCategory::class);
    }
}
