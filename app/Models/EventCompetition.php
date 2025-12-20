<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;


class EventCompetition extends Model
{
    protected $table = 'event_competitions';

    protected $guarded = [];

    protected $casts = [
        'is_team'        => 'boolean',
        'scheduled_at'   => 'datetime:Y-m-d',
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
     * CGL event (event_group)
     */
    public function eventGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\EventGroup::class, 'event_group_id');
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

    public function groupJudges(): HasMany
    {
        return $this->hasMany(\App\Models\EventGroupJudge::class, 'event_group_id', 'event_group_id');
    }

    public function branchJudges(): Builder
    {
        // ambil branch_id dari event_group milik competition ini
        $branchId = $this->eventGroup?->branch_id;

        if (!$branchId) {
            // kalau eventGroup belum diload, fallback: join event_groups
            $branchId = \App\Models\EventGroup::whereKey($this->event_group_id)->value('branch_id');
        }

        return \App\Models\EventBranchJudge::query()
            ->whereHas('eventBranch', function ($q) use ($branchId) {
                $q->where('event_id', $this->event_id)
                ->where('branch_id', $branchId);
            });
    }

    public function judgesQuery()
    {
        $useCustom = (bool) ($this->eventGroup?->use_custom_judges);

        return $useCustom
            ? $this->groupJudges()
            : $this->branchJudges(); // query builder dari opsi A
    }

    public function scoresheets() { return $this->hasMany(EventScoresheet::class, 'event_competition_id'); }





}
