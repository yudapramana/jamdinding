<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'participants';

    protected $fillable = [
        'nik',
        'full_name',
        'phone_number',
        'place_of_birth',
        'date_of_birth',
        'gender',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'address',
        'education',
        'bank_account_number',
        'bank_account_name',
        'bank_name',
        'photo_url',
        'id_card_url',
        'family_card_url',
        'bank_book_url',
        'certificate_url',
        'other_url',
        'tanggal_terbit_ktp',
        'tanggal_terbit_kk',
    ];

    protected $casts = [
        'date_of_birth'       => 'date:Y-m-d',
        'tanggal_terbit_ktp'  => 'date:Y-m-d',
        'tanggal_terbit_kk'   => 'date:Y-m-d',
    ];

    /* ============================
     *  RELATIONSHIPS
     * ============================
     */

    // wilayah
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

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    // relasi ke tabel pivot event_participants
    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    // many-to-many: participant ↔ event melalui event_participants
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_participants')
            ->using(EventParticipant::class)
            ->withPivot([
                'event_competition_branch_id',
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
     *  ACCESSOR / HELPER
     * ============================
     */

    public function getFullAddressAttribute()
    {
        $parts = [
            $this->address,
            optional($this->village)->name,
            optional($this->district)->name,
            optional($this->regency)->name,
            optional($this->province)->name,
        ];

        return trim(collect($parts)->filter()->implode(', '));
    }
}
