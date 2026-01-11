<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventFieldComponentJudge extends Model
{
    use HasFactory;

    protected $table = 'event_field_component_judges';

    protected $fillable = [
        'event_field_component_id',
        'event_judge_panel_id',
        'event_judge_id',
        'is_chief',
        'order_number',
    ];

    protected $casts = [
        'is_chief' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Komponen penilaian (event_field_components)
     */
    public function eventFieldComponent()
    {
        return $this->belongsTo(
            EventFieldComponent::class,
            'event_field_component_id'
        );
    }

    /**
     * Juri event (event_judges)
     */
    public function eventJudge()
    {
        return $this->belongsTo(
            EventJudge::class,
            'event_judge_id'
        );
    }

    /**
     * Majelis hakim (event_judge_panels)
     * Nullable: BY_COMPONENT tapi tetap referensi sumber juri
     */
    public function eventJudgePanel()
    {
        return $this->belongsTo(
            EventJudgePanel::class,
            'event_judge_panel_id'
        );
    }
}
