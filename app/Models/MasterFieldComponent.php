<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterFieldComponent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'default_weight' => 'integer',
        'default_max_score' => 'integer',
        'default_order' => 'integer',
        'is_default' => 'boolean',
    ];

    public function masterGroup()
    {
        return $this->belongsTo(MasterGroup::class);
    }

    public function field()
    {
        return $this->belongsTo(ListField::class, 'field_id');
    }
}
