<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventAssessmentHeader extends Model
{
    protected $table = 'event_assessment_headers';

    protected $guarded = [];

    protected $casts = [
        'total_score' => 'decimal:2',
        'judged_at'   => 'datetime',
    ];

    // --------------------
    // RELASI UTAMA
    // --------------------

    /**
     * Event induk
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Lomba di babak tertentu (event_competitions)
     */
    public function eventCompetition(): BelongsTo
    {
        return $this->belongsTo(EventCompetition::class, 'event_competition_id');
    }

    /**
     * CGL event (event_competition_branches)
     */
    public function eventCompetitionBranch(): BelongsTo
    {
        return $this->belongsTo(EventCompetitionBranch::class, 'event_competition_branch_id');
    }

    /**
     * Peserta yang dinilai (event_participants)
     */
    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class, 'event_participant_id');
    }

    /**
     * Hakim (users)
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    /**
     * Detail skor per komponen bidang penilaian
     */
    public function scores(): HasMany
    {
        return $this->hasMany(EventAssessmentScore::class, 'event_assessment_header_id');
    }
}
