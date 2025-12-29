<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventContingentMedal extends Model
{
    use HasFactory;

    protected $table = 'event_contingent_medals';

    protected $fillable = [
        'event_contingent_id',
        'order_number',
        'medal_code',
        'medal_name',
        'medal_count',
    ];

    /* =====================
     |  Relationships
     ===================== */

    /**
     * Kontingen pemilik medali
     */
    public function eventContingent()
    {
        return $this->belongsTo(EventContingent::class);
    }

    /* =====================
     |  Helpers (Optional)
     ===================== */

    /**
     * Increment medal count safely
     */
    public function incrementCount(int $by = 1): void
    {
        $this->increment('medal_count', $by);
    }
}
