<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAssessmentScore extends Model
{
    protected $table = 'event_assessment_scores';

    protected $guarded = [];

    protected $casts = [
        'score'          => 'decimal:2',
        'max_score'      => 'decimal:2',
        'weight'         => 'decimal:2',
        'weighted_score' => 'decimal:2',
    ];

    /**
     * Header penilaian induk (1 peserta + 1 hakim + 1 event_competition)
     */
    public function header(): BelongsTo
    {
        return $this->belongsTo(EventAssessmentHeader::class, 'event_assessment_header_id');
    }

    /**
     * Komponen bidang penilaian di level event
     * (turunan dari master_branch_field_components)
     */
    public function eventBranchFieldComponent(): BelongsTo
    {
        return $this->belongsTo(EventBranchFieldComponent::class, 'event_branch_field_component_id');
    }
}
