<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Work;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $monthYear = request()->query('monthyear');

        if($monthYear) {

            $user_id = request()->has('user_id') ? request()->query('user_id') : auth()->user()->id;
            
            // $now = Carbon::now();
            // $year = $now->format('Y');
            // $month = $now->format('m');

            $year = substr($monthYear, 0, 4);
            $month = substr($monthYear, 5, 2);
    
            $reports = Report::query()
                        ->where([
                            'year' => $year,
                            'month' => $month,
                            'user_id' => $user_id
                        ])
                        ->orderBy('date', 'DESC')
                        ->with('works')
                        ->paginate(setting('pagination_limit'));
        } else {
            return collect(
                [
                    'data' => []
                ]
            );
        }

        

        return $reports;
    }

    public function store()
    {

        $validated = request()->validate([
            'date' => 'required|date',
            'work_name' => 'required',
            'work_detail' => 'sometimes',
            'volume'    => 'required|numeric|min:1',
            'unit'  => 'required',
            'evidence' => 'required',
            'evidence_url' => 'sometimes',
        ]);


        $cDate = Carbon::createFromFormat('Y-m-d', $validated['date']);

        $user_id = auth()->user()->id;

        $report = Report::where([
            'user_id' => $user_id,
            'date' => $validated['date'],
        ])->first();

        if(!$report) {
            $report = new Report();
            $report->user_id = $user_id;
            $report->date = $validated['date'];
            $report->year = $cDate->format('Y');
            $report->month = $cDate->format('m');
            $report->day = $cDate->format('d');
            $report->save();
            $report->fresh();
        }

        $work = new Work();
        $work->report_id = $report->id;
        $work->work_name = $validated['work_name'];
        $work->work_detail = $validated['work_detail'];
        $work->volume = $validated['volume'];
        $work->unit = $validated['unit'];
        $work->evidence = $validated['evidence'];
        $work->evidence_url = $validated['evidence_url'];
        $work->save();

        return response()->json(['message' => 'Laporan kerja sukses dibuat!']);

    }

    public function edit(Work $work)
    {
        $work->load('report');
        return $work;
    }

    public function update(Work $work)
    {
        $validated = request()->validate([
            'date' => 'required|date',
            'work_name' => 'required',
            'work_detail' => 'required',
            'volume'    => 'required|numeric|min:1',
            'unit'  => 'required',
            'evidence' => 'required',
            'evidence_url' => 'sometimes',
        ]);


        $work->load('report');

        $user_id = auth()->user()->id;
        if($work->report->date != $validated['date']) {

            $cDate = Carbon::createFromFormat('Y-m-d', $validated['date']);
            $report = Report::firstOrCreate([
                'user_id' => $user_id,
                'date' => $validated['date'],
                'year' => $cDate->format('Y'),
                'month' => $cDate->format('m'),
                'day' => $cDate->format('d'),
            ]);

            $work->report_id = $report->id;
        }

        $work->work_name = $validated['work_name'];
        $work->work_detail = $validated['work_detail'];
        $work->volume = $validated['volume'];
        $work->unit = $validated['unit'];
        $work->evidence = $validated['evidence'];
        $work->evidence_url = $validated['evidence_url'];
        $work->save();

        return response()->json(['success' => true]);

    }

    public function destroy(Work $work)
    {
        $work->delete();

        return response()->json(['success' => true], 200);
    }

    public function destroyParent(Report $report)
    {
        $report->delete();

        return response()->json(['success' => true], 200);
    }
}
