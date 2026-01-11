<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventJudgePanelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'code'              => $this->code,
            'name'              => $this->name,
            'notes'             => $this->notes,
            'is_active'         => $this->is_active,
            'event_location_id' => $this->event_location_id,

            'judges' => $this->members
                ->sortBy('order_number')
                ->map(fn ($m) => [
                    'event_judge_id' => $m->event_judge_id,
                    'full_name'      => $m->eventJudge->masterJudge->full_name,
                    'is_chief'       => $m->is_chief,
                    'order_number'   => $m->order_number,
                ])
                ->values()
        ];
    }
}
