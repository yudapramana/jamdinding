<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['unit_value'];

    public function unit(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => UnitType::from($value)->name,
        );
    }

    public function report() {
        return $this->belongsTo(Report::class, 'report_id', 'id');
    }

    public function getUnitValueAttribute() {
        return $this->attributes['unit'];
    }
}
