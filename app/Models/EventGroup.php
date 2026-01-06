<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_team' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function location()
    {
        return $this->belongsTo(EventLocation::class, 'event_location_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function categories()
    {
        return $this->hasMany(EventCategory::class);
    }

    public function fieldComponents()
    {
        return $this->hasMany(EventFieldComponent::class);
    }

    public function competitions()
    {
        return $this->hasMany(EventCompetition::class);
    }

    public function scoresheets()
    {
        return $this->hasMany(EventScoresheet::class);
    }

    /**
     * Judges khusus golongan ini (override).
     */
    public function customJudges(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_group_judges', 'event_group_id', 'user_id')
            ->withPivot(['is_chief'])
            ->withTimestamps();
    }

    /**
     * Ambil default EventBranch (untuk akses judges cabang).
     * (Relasi ini aman karena event_branches memang punya event_id+branch_id.)
     */
    public function eventBranch(): BelongsTo
    {
        // trik: belongsTo tidak bisa pakai 2 kolom FK langsung.
        // jadi biasanya dipakai via method helper di bawah.
        return $this->belongsTo(EventBranch::class, 'branch_id', 'branch_id');
    }

    /**
     * Helper: juri efektif (custom jika override, kalau tidak ambil default cabang).
     */
    public function effectiveJudges()
    {
        if ($this->use_custom_judges) {
            return $this->customJudges();
        }

        $eventBranchId = EventBranch::query()
            ->where('event_id', $this->event_id)
            ->where('branch_id', $this->branch_id)
            ->value('id');

        return User::query()
            ->whereIn('id', function ($q) use ($eventBranchId) {
                $q->from('event_branch_judges')
                  ->select('user_id')
                  ->where('event_branch_id', $eventBranchId ?? 0);
            });
    }
}
