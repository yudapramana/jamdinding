<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBranchJudge extends Model
{
    protected $table = 'event_branch_judges';

    protected $guarded = [];

    protected $casts = [
        'is_chief' => 'boolean',
        'order_no' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(EventCompetitionBranch::class, 'event_competition_branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
