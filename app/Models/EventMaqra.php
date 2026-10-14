<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMaqra extends Model
{
    use HasFactory;

    protected $table = 'event_maqras';

    protected $fillable = [
        'event_group_id',
        'surah_number',
        'surah_name',
        'start_ayah',
        'end_ayah',
        'juz',
        'page',
        'is_available',
    ];

    protected $casts = [
        'surah_number' => 'integer',
        'start_ayah' => 'integer',
        'end_ayah' => 'integer',
        'juz' => 'integer',
        'page' => 'integer',
        'is_available' => 'boolean',
    ];

    /**
     * Relasi ke EventGroup
     */
    public function eventGroup(): BelongsTo
    {
        return $this->belongsTo(EventGroup::class);
    }
}