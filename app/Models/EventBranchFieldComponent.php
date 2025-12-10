<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventBranchFieldComponent extends Model
{
    use HasFactory;

    protected $table = 'event_branch_field_components';

    protected $fillable = [
        'event_competition_branch_id',
        'assessment_field_id',
        'master_branch_field_component_id',
        'weight',
        'max_score',
        'order',
        'is_active',
    ];

    protected $casts = [
        'weight'    => 'integer',   // atau 'float' kalau kamu pakai desimal
        'max_score' => 'integer',
        'order'     => 'integer',
        'is_active' => 'boolean',
    ];

    /* ===============================
     * RELATIONS
     * =============================== */

    public function eventCompetitionBranch()
    {
        return $this->belongsTo(EventCompetitionBranch::class, 'event_competition_branch_id');
    }

    public function assessmentField()
    {
        return $this->belongsTo(AssessmentField::class, 'assessment_field_id');
    }

    public function masterComponent()
    {
        return $this->belongsTo(MasterBranchFieldComponent::class, 'master_branch_field_component_id');
    }

    public function assessmentScores()
    {
        return $this->hasMany(EventAssessmentScore::class, 'event_branch_field_component_id');
    }


    /**
     * (opsional) relasi ke tabel nilai per peserta, kalau sudah ada
     * misal: event_branch_scores
     */
    // public function scores()
    // {
    //     return $this->hasMany(EventBranchScore::class, 'event_branch_field_component_id');
    // }
}
