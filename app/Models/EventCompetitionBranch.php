<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventCompetitionBranch extends Model
{
    protected $table = 'event_competition_branches';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'max_age'   => 'integer',
        'required_judges' => 'integer',
        'min_judges'      => 'integer',
        'max_judges'      => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competitions()
    {
        return $this->hasMany(EventCompetition::class, 'event_competition_branch_id');
    }

    public function eventBranchFieldComponents()
    {
        return $this->hasMany(EventBranchFieldComponent::class, 'event_competition_branch_id');
    }

    /**
     * Anggota panel hakim untuk branch ini.
     */
    public function branchJudges(): HasMany
    {
        return $this->hasMany(EventBranchJudge::class, 'event_competition_branch_id');
    }

    /**
     * Hakim-hakim (User) yang menjadi panel branch ini (many-to-many).
     */
    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'event_branch_judges',
            'event_competition_branch_id',
            'user_id'
        )
        ->withPivot(['role_in_panel', 'order_no', 'is_chief'])
        ->withTimestamps();
    }

    public function assessmentHeaders()
    {
        return $this->hasMany(EventAssessmentHeader::class, 'event_competition_branch_id');
    }

    public function masterBranch()
    {
        return $this->belongsTo(MasterCompetitionBranch::class, 'master_competition_branch_id');
    }

    public function group()
    {
        return $this->belongsTo(MasterCompetitionGroup::class, 'master_competition_group_id');
    }

    public function category()
    {
        return $this->belongsTo(MasterCompetitionCategory::class, 'master_competition_category_id');
    }

    /**
     * Bidang penilaian yang dipakai di cabang ini (di level event)
     */
    public function fieldComponents()
    {
        return $this->hasMany(EventBranchFieldComponent::class, 'event_competition_branch_id')
                    ->orderBy('order');
    }

    /**
     * Peserta / entry peserta yang ikut di cabang ini
     * (sesuaikan nama model & FK pada tabelmu)
     */
    public function participantBranches()
    {
        return $this->hasMany(EventParticipantBranch::class, 'event_competition_branch_id');
    }

    /**
     * Helper: nama cabang final (pakai override kalau ada)
     */
    public function getDisplayNameAttribute()
    {
        if (!empty($this->name_override)) {
            return $this->name_override;
        }

        return $this->masterBranch?->name ?? '';
    }

    public function getDisplayCodeAttribute()
    {
        if (!empty($this->code_override)) {
            return $this->code_override;
        }

        return $this->masterBranch?->code ?? '';
    }
}
