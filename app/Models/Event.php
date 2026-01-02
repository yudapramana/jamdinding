<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;


class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $guarded = [];

    protected $casts = [
        'start_date'         => 'date:Y-m-d',
        'end_date'           => 'date:Y-m-d',
        'age_limit_date'     => 'date:Y-m-d',
        'is_active'          => 'boolean',
    ];

    /* ============================
     *  RELATIONSHIPS
     * ============================
     */

    // wilayah event
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function stages()
    {
        return $this->belongsToMany(Stage::class, 'event_stages')
            ->using(EventStage::class)
            ->withPivot(['order_number', 'name', 'start_date', 'end_date', 'notes', 'is_active'])
            ->withTimestamps();
    }

    public function eventStages()
    {
        return $this->hasMany(EventStage::class);
    }

    public function eventBranches()
    {
        return $this->hasMany(EventBranch::class);
    }

    public function eventGroups()
    {
        return $this->hasMany(EventGroup::class);
    }

    public function eventCategories()
    {
        return $this->hasMany(EventCategory::class);
    }

    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function eventCompetitions()
    {
        return $this->hasMany(EventCompetition::class);
    }

    public function medalStandings()
    {
        return $this->hasMany(MedalStanding::class);
    }

    public function eventContingents()
    {
        return $this->hasMany(EventContingent::class);
    }

    // many-to-many ke peserta
    public function participants()
    {
        return $this->belongsToMany(Participant::class, 'event_participants')
            ->using(EventParticipant::class)
            ->withPivot([
                'event_group_id',
                'age_year',
                'age_month',
                'age_day',
                'status_pendaftaran',
                'registration_notes',
                'moved_by',
                'verified_by',
                'verified_at',
                'created_at',
                'updated_at',
            ]);
    }

    /* ============================
     *  SCOPES
     * ============================
     */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // use App\Models\ParticipantVerification;

    public function participantVerifications()
    {
        return $this->hasMany(ParticipantVerification::class);
    }

    /**
     * Cek apakah stage tertentu sedang aktif untuk event ini
     *
     * @param string|int $stage   nama stage (pendaftaran) atau stage_id
     * @param Carbon|null $at     waktu referensi (default: now)
     * @return bool
     */
    public function isStageActive($stage, ?Carbon $at = null): bool
    {
        $at = $at ?: Carbon::now();

        return $this->eventStages()
            ->where('is_active', true)
            ->where(function ($q) use ($stage) {
                if (is_numeric($stage)) {
                    // berdasarkan stage_id
                    $q->where('stage_id', $stage);
                } else {
                    // berdasarkan nama stage
                    $q->whereRaw('LOWER(name) = ?', [strtolower($stage)]);
                }
            })
            ->whereDate('start_date', '<=', $at)
            ->whereDate('end_date', '>=', $at)
            ->exists();
    }

    public function activeStage(): ?EventStage
    {
        $now = Carbon::now();

        return $this->eventStages()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->orderBy('order_number')
            ->first();
    }

    public function isRegistrationOpen(): bool
    {
        return $this->isStageActive('pendaftaran');
    }

    public function isPreparationOpen(): bool
    {
        return $this->isStageActive('persiapan');
    }




}
