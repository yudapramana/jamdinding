<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventParticipant extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'event_participants';

    // agar Laravel memperlakukan ini seperti model biasa
    public $incrementing = true;
    protected $keyType   = 'int';

    protected $fillable = [
        'event_id',
        'participant_id',
        'event_competition_branch_id',
        'age_year',
        'age_month',
        'age_day',
        'status_pendaftaran',
        'registration_notes',
        'moved_by',
        'verified_by',
        'verified_at',
        'status_daftar_ulang',
        'daftar_ulang_at',
        'daftar_ulang_by'

    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /* ============================
     *  CONSTANTS STATUS
     * ============================
     */

    const STATUS_BANKDATA = 'bankdata';
    const STATUS_PROSES   = 'proses';
    const STATUS_DITERIMA = 'diterima';
    const STATUS_PERBAIKI = 'perbaiki';
    const STATUS_MUNDUR   = 'mundur';
    const STATUS_TOLAK    = 'tolak';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_BANKDATA,
            self::STATUS_PROSES,
            self::STATUS_DITERIMA,
            self::STATUS_PERBAIKI,
            self::STATUS_MUNDUR,
            self::STATUS_TOLAK,
        ];
    }

    /* ============================
     *  RELATIONSHIPS
     * ============================
     */

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function eventBranch()
    {
        return $this->belongsTo(EventBranch::class, 'event_branch_id');
    }

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class, 'event_group_id');
    }

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function scoresheets()
    {
        return $this->hasMany(EventScoresheet::class);
    }

    public function mover()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /* ============================
     *  SCOPES & HELPERS
     * ============================
     */

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status_pendaftaran', $status);
    }

    public function getAgeTextAttribute()
    {
        return sprintf(
            '%dT %dB %dH',
            $this->age_year,
            $this->age_month,
            $this->age_day
        );
    }

    public function markVerified(User $user = null)
    {
        $this->status_pendaftaran = self::STATUS_DITERIMA;
        $this->verified_by        = $user ? $user->id : auth()->id();
        $this->verified_at        = now();
        $this->save();
    }

    // use App\Models\ParticipantVerification;

    public function verifications()
    {
        return $this->hasMany(ParticipantVerification::class, 'event_participant_id');
    }

    public function assessmentHeaders()
    {
        return $this->hasMany(EventAssessmentHeader::class, 'event_participant_id');
    }

}
