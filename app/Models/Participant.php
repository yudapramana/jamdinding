<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'event_competition_branch_id',
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

    // RELATIONS
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competitionBranch()
    {
        return $this->belongsTo(EventCompetitionBranch::class, 'event_competition_branch_id');
    }

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
}
