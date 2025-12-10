<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterBranchFieldComponent extends Model
{
    use HasFactory;

    protected $table = 'master_branch_field_components';

    protected $fillable = [
        'master_competition_branch_id',
        'assessment_field_id',
        'default_weight',
        'default_max_score',
        'default_order',
        'is_default',
    ];

    protected $casts = [
        'default_weight'    => 'integer',
        'default_max_score' => 'integer',
        'default_order'     => 'integer',
        'is_default'        => 'boolean',
    ];

    /* ===============================
     * RELATIONS
     * =============================== */

    public function branch()
    {
        return $this->belongsTo(MasterCompetitionBranch::class, 'master_competition_branch_id');
    }

    public function assessmentField()
    {
        return $this->belongsTo(AssessmentField::class, 'assessment_field_id');
    }

    /**
     * Event-level komponen yang di-clone dari template ini
     */
    public function eventComponents()
    {
        return $this->hasMany(EventBranchFieldComponent::class, 'master_branch_field_component_id');
    }
}
