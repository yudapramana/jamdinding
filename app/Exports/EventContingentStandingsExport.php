<?php

namespace App\Exports;

use App\Http\Controllers\Api\EventContingentStandingsController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EventContingentStandingsExport implements FromArray, WithHeadings
{
    protected int $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function headings(): array
    {
        return [
            'Kontingen',
            'Juara 1',
            'Juara 2',
            'Juara 3',
            'Harapan 1',
            'Harapan 2',
            'Harapan 3',
            'Total Poin',
        ];
    }

    public function array(): array
    {
        $controller = new EventContingentStandingsController();

        $response = $controller->index(
            new Request(['event_id' => $this->eventId])
        );

        $rows = $response->getData(true)['data'];

        return collect($rows)->map(function ($row) {
            return [
                $row['contingent'],
                $row['medals'][1] ?? 0,
                $row['medals'][2] ?? 0,
                $row['medals'][3] ?? 0,
                $row['medals'][4] ?? 0,
                $row['medals'][5] ?? 0,
                $row['medals'][6] ?? 0,
                $row['total_point'],
            ];
        })->toArray();
    }
}
