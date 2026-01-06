<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLocation extends Model
{
    use HasFactory;

    protected $table = 'event_locations';

    /**
     * Kolom yang boleh diisi mass-assignment
     */
    protected $fillable = [
        'event_id',
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'notes',
        'is_active',
    ];

    /**
     * Casting tipe data
     */
    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    /* =====================================================
     * RELATIONSHIPS
     * ===================================================== */

    /**
     * Lokasi milik satu event MTQ
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Satu lokasi bisa digunakan banyak cabang / majelis
     */
    public function eventGroups()
    {
        return $this->hasMany(EventGroup::class, 'event_location_id');
    }

    /* =====================================================
     * SCOPES
     * ===================================================== */

    /**
     * Hanya lokasi aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter lokasi berdasarkan event
     */
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /* =====================================================
     * ACCESSORS
     * ===================================================== */

    /**
     * Format koordinat siap pakai (lat,lng)
     */
    public function getCoordinateAttribute(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }

    /* =====================================================
     * HELPERS (SIAP DIPAKAI NANTI)
     * ===================================================== */

    /**
     * Hitung jarak (meter) dari titik lain (Haversine)
     * Cocok untuk absensi GPS
     */
    public function distanceFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // meter

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo   = deg2rad($lat);
        $lonTo   = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) *
            pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }
}
