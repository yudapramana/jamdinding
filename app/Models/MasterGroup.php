<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_team' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function categories()
    {
        return $this->hasMany(MasterCategory::class);
    }

    public function fieldComponents()
    {
        return $this->hasMany(MasterFieldComponent::class);
    }
}
