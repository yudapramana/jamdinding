<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventContingent extends Model
{
    use HasFactory;

    protected $table = 'event_contingents';

    protected $fillable = [
        'event_id',
        'region_type',
        'region_id',
        'total_participant',
        'total_point',
    ];

    /* =========================
     |  CONSTANTS (REGION TYPE)
     ========================= */
    public const REGION_PROVINCE = 'province';
    public const REGION_REGENCY  = 'regency';
    public const REGION_DISTRICT = 'district';
    public const REGION_VILLAGE  = 'village';

    /* =========================
     |  RELATIONSHIPS
     ========================= */

    /**
     * Event pemilik kontingen
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Polymorphic region
     * province / regency / district / village
     */
    public function region()
    {
        return $this->morphTo(null, 'region_type', 'region_id');
    }

    /**
     * Breakdown perolehan medali
     */
    public function medals()
    {
        return $this->hasMany(EventContingentMedal::class);
    }

    /* =========================
     |  ACCESSORS
     ========================= */

    /**
     * Nama wilayah (safe)
     */
    // public function getRegionNameAttribute(): string
    // {
    //     return $this->region?->name ?? '-';
    // }

    public function getRegionNameAttribute(): string
    {
        return match ($this->region_type) {
            self::REGION_PROVINCE =>
                \DB::table('provinces')->where('id', $this->region_id)->value('name'),

            self::REGION_REGENCY =>
                \DB::table('regencies')->where('id', $this->region_id)->value('name'),

            self::REGION_DISTRICT =>
                \DB::table('districts')->where('id', $this->region_id)->value('name'),

            self::REGION_VILLAGE =>
                \DB::table('villages')->where('id', $this->region_id)->value('name'),

            default => '-',
        } ?? '-';
    }


    /* =========================
     |  SCOPES
     ========================= */

    /**
     * Filter by event
     */
    public function scopeByEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Order by total point DESC
     */
    public function scopeOrderByPoint($query)
    {
        return $query->orderByDesc('total_point');
    }
}
