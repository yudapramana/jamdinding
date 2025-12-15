<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventBranch extends Model
{
    protected $table = 'event_branches';

    protected $guarded = [];

    protected $casts = [
        'order_number' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function eventGroups(): HasMany
    {
        return $this->hasMany(EventGroup::class, 'branch_id', 'branch_id')
            ->whereColumn('event_groups.event_id', 'event_branches.event_id');
    }

    /**
     * Default judges per cabang (berlaku untuk semua golongan kecuali override).
     */
    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_branch_judges', 'event_branch_id', 'user_id')
            ->withPivot(['is_chief'])
            ->withTimestamps();
    }
}
