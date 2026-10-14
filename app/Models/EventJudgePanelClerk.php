<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventJudgePanelClerk extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_judge_panel_id',
        'user_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Majelis Hakim (Setiap penugasan terikat pada 1 Majelis)
     */
    public function eventJudgePanel(): BelongsTo
    {
        return $this->belongsTo(EventJudgePanel::class, 'event_judge_panel_id');
    }

    /**
     * Relasi ke User Panitera (Setiap penugasan terikat pada 1 User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}