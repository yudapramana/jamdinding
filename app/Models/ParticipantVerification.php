<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantVerification extends Model
{
    use HasFactory;

    protected $table = 'participant_verifications';

    protected $fillable = [
        'participant_id',
        'event_id',
        'event_participant_id',
        'verified_by',
        'status',

        'checked_photo',
        'checked_id_card',
        'checked_family_card',
        'checked_bank_book',
        'checked_certificate',
        'checked_other',

        'checked_identity',
        'checked_contact',
        'checked_domicile',
        'checked_education',
        'checked_bank_account',
        'checked_document_dates',

        'field_matches',
        'notes',
    ];

    protected $casts = [
        'checked_photo'           => 'boolean',
        'checked_id_card'         => 'boolean',
        'checked_family_card'     => 'boolean',
        'checked_bank_book'       => 'boolean',
        'checked_certificate'     => 'boolean',
        'checked_other'           => 'boolean',

        'checked_identity'        => 'boolean',
        'checked_contact'         => 'boolean',
        'checked_domicile'        => 'boolean',
        'checked_education'       => 'boolean',
        'checked_bank_account'    => 'boolean',
        'checked_document_dates'  => 'boolean',

        'field_matches'           => 'array',
    ];

    /* ============================
     *  RELATIONSHIPS
     * ============================
     */

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function eventParticipant()
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /* ============================
     *  HELPERS
     * ============================
     */

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
