<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCompetition extends Model
{
    protected $table = 'event_competitions';

    protected $guarded = [];

    protected $casts = [
        'is_team'        => 'boolean',
        'schedule_start' => 'datetime:Y-m-d',
        'schedule_end'   => 'datetime:Y-m-d',
    ];

    // --------------------
    // RELASI UTAMA
    // --------------------

    /**
     * Event induk (events)
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * CGL event (event_competition_branches)
     */
    public function eventCompetitionBranch(): BelongsTo
    {
        return $this->belongsTo(EventCompetitionBranch::class, 'event_competition_branch_id');
    }

    /**
     * Babak / Round (Penyisihan, Semifinal, Final, ...)
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    /**
     * Header penilaian di babak ini
     */
    public function assessmentHeaders(): HasMany
    {
        return $this->hasMany(EventAssessmentHeader::class, 'event_competition_id');
    }

    public function branchJudges()
    {
        return $this->hasManyThrough(
            EventBranchJudge::class,
            EventCompetitionBranch::class,
            'id',   // local key di EventCompetitionBranch
            'event_competition_branch_id', // foreign key EventBranchJudge
            'event_competition_branch_id', // local key di EventCompetition (ke branch)
            'id'   // local key di EventCompetitionBranch
        );
    }



}
