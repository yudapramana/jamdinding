<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class EventParticipant extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'event_participants';
    public $incrementing = true;
    protected $keyType   = 'int';

    protected $fillable = [
        'event_id',
        'participant_id',

        'event_branch_id',
        'event_group_id',
        'event_category_id',

        'age_year',
        'age_month',
        'age_day',

        'contingent',

        'registration_status',
        'registration_notes',
        'register_at',

        'moved_by',
        'verified_by',
        'verified_at',

        'reregistration_status',
        'reregistered_at',
        'reregistered_by',
        'reregistration_notes',
        'branch_code',
        'branch_sequence',
        'participant_number',
    ];

    protected $casts = [
        'register_at'      => 'datetime',
        'verified_at'      => 'datetime',
        'reregistered_at'  => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ============================
    // STATUS (sesuai migration)
    // ============================
    const REG_BANK_DATA     = 'bank_data';
    const REG_PROCESS       = 'process';
    const REG_VERIFIED      = 'verified';
    const REG_NEED_REVISION = 'need_revision';
    const REG_REJECTED      = 'rejected';
    const REG_DISQUALIFIED  = 'disqualified';

    public static function registrationStatusOptions(): array
    {
        return [
            self::REG_BANK_DATA,
            self::REG_PROCESS,
            self::REG_VERIFIED,
            self::REG_NEED_REVISION,
            self::REG_REJECTED,
            self::REG_DISQUALIFIED,
        ];
    }

    const REREG_NOT_YET  = 'not_yet';
    const REREG_VERIFIED = 'verified';
    const REREG_REJECTED = 'rejected';

    public static function reregistrationStatusOptions(): array
    {
        return [self::REREG_NOT_YET, self::REREG_VERIFIED, self::REREG_REJECTED];
    }

    // ============================
    // RELATIONSHIPS
    // ============================
    public function event() { return $this->belongsTo(Event::class); }
    public function participant() { return $this->belongsTo(Participant::class); }

    public function eventBranch() { return $this->belongsTo(EventBranch::class, 'event_branch_id'); }
    public function eventGroup() { return $this->belongsTo(EventGroup::class, 'event_group_id'); }
    public function eventCategory() { return $this->belongsTo(EventCategory::class, 'event_category_id'); }

    public function mover() { return $this->belongsTo(User::class, 'moved_by'); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
    public function reregistrator() { return $this->belongsTo(User::class, 'reregistered_by'); }

    /**
     * Semua log verifikasi untuk event_participant ini.
     * FK: participant_verifications.event_participant_id -> event_participants.id
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(ParticipantVerification::class, 'event_participant_id', 'id');
    }

    /**
     * Verifikasi terakhir (paling baru).
     */
    public function latestVerification(): HasOne
    {
        // Laravel 8+ recommended
        return $this->hasOne(ParticipantVerification::class, 'event_participant_id', 'id')
            ->latestOfMany();
    }

    // ============================
    // HELPERS
    // ============================
    public function getAgeTextAttribute()
    {
        return sprintf('%dT %dB %dH', $this->age_year, $this->age_month, $this->age_day);
    }

    public function markVerified(User $user = null)
    {
        $this->registration_status = self::REG_VERIFIED;
        $this->verified_by = $user ? $user->id : auth()->id();
        $this->verified_at = now();
        $this->save();
    }

    public function team()
    {
        return $this->belongsTo(EventTeam::class, 'event_team_id');
    }
}
