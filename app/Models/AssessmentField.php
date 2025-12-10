<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssessmentField extends Model
{
    use HasFactory;

    protected $table = 'assessment_fields';

    protected $fillable = [
        'code',         // optional
        'field_name',   // misal: 'Tajwid', 'Lagu', 'Suara'
        'description',  // optional
        'default_unit', // optional, misal: 'point'
    ];

    /* ===============================
     * RELATIONS
     * =============================== */

    /**
     * Dipakai di template master per cabang
     */
    public function masterBranchFieldComponents()
    {
        return $this->hasMany(MasterBranchFieldComponent::class, 'assessment_field_id');
    }

    /**
     * Dipakai di level event per cabang
     */
    public function eventBranchFieldComponents()
    {
        return $this->hasMany(EventBranchFieldComponent::class, 'assessment_field_id');
    }
}
