<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventJudgePanel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['roman_name'];

    /* =====================
     * RELATIONS
     * ===================== */

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function members()
    {
        return $this->hasMany(EventJudgePanelMember::class);
    }

    public static function nextNumberForEvent(int $eventId): int
    {
        return self::where('event_id', $eventId)->count() + 1;
    }

    public function eventGroups()
    {
        return $this->hasMany(EventGroup::class);
    }

    public function location()
    {
        return $this->belongsTo(EventLocation::class, 'event_location_id');
    }

    /* =====================
     * HELPERS
     * ===================== */

    /**
     * Konversi angka ke Romawi
     */
    public static function toRoman(int $number): string
    {
        $map = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1,
        ];

        $result = '';

        foreach ($map as $roman => $value) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }

    /**
     * Ambil nomor majelis dari name (contoh: "Majelis 15")
     */
    public function getNumberAttribute(): ?int
    {
        if (! $this->name) {
            return null;
        }

        preg_match('/\d+/', $this->name, $matches);

        return isset($matches[0]) ? (int) $matches[0] : null;
    }

    /**
     * Nomor majelis dalam format Romawi (XV)
     */
    public function getRomanNumberAttribute(): ?string
    {
        return $this->number
            ? self::toRoman($this->number)
            : null;
    }

    /**
     * Nama majelis versi Romawi (Majelis XV)
     */
    public function getRomanNameAttribute(): string
    {
        return $this->roman_number
            ? 'Majelis ' . $this->roman_number
            : $this->name;
    }

}
