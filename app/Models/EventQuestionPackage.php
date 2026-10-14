<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventQuestionPackage extends Model
{
    protected $table = 'event_question_packages';

    protected $fillable = [
        'event_group_id',
        'package_number',
        'question_content',
        'is_available',
    ];

    protected $casts = [
        'question_content' => 'array',
        'is_available'     => 'boolean',
    ];

    public function eventGroup(): BelongsTo
    {
        return $this->belongsTo(EventGroup::class);
    }
}