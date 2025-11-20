<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_key',
        'nama_event',
        'nama_aplikasi',
        'lokasi_event',
        'tagline',
        'token_hasil_penilaian',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_batas_umur',
        'logo_event',
        'logo_sponsor_1',
        'logo_sponsor_2',
        'logo_sponsor_3',
        'tingkat_event',
        'province_id',
        'regency_id',
        'district_id',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai'       => 'date:Y-m-d',
        'tanggal_selesai'     => 'date:Y-m-d',
        'tanggal_batas_umur'  => 'date:Y-m-d',
        'is_active'           => 'boolean',
    ];

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
}
