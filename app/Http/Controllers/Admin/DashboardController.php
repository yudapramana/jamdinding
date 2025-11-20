<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Report;
use App\Models\Work;
use Illuminate\Http\Request;
use DB;

class DashboardController extends Controller
{
    public function reports()
    {
        $monthYear = request('monthyear');
        $year = substr($monthYear, 0, 4);
        $month = substr($monthYear, 5, 2);
        $user_id = auth()->user()->id;

        $totalWorks = Work::query()
                            ->whereHas('report', function ($q) use ($year, $month, $user_id) {
                                $q->where([
                                    'year' => $year,
                                    'month' => $month,
                                    'user_id' => $user_id
                                ]);
                            })->get();

        $totalWorksCount = $totalWorks->count();
        $totalReportsCount = $totalWorks->pluck('report_id')->unique()->count();


        return response()->json([
            'totalReportsCount' => $totalReportsCount,
            'totalWorksCount' => $totalWorksCount,
        ]);
    }

    public function all()
    {
        $monthYear = request('monthyear');

        $year = substr($monthYear, 0, 4);
        $month = substr($monthYear, 5, 2);

        $totalWorks = Report::query()
                            ->where([
                                'year' => $year,
                                'month' => $month
                            ])
                            ->get();

        $users = $totalWorks->pluck('user')->unique()->values();

        $usersGrouped = $users->groupBy('org_name')->map->count();
        $usersGrouped = $usersGrouped->toArray();

        $orgs = Organization::where('name', 'like', '%Kantor Kementerian Agama%')->withCount('users')->get();

        // return $orgs;

        $data = $orgs->map(function ($item, $key) use ($usersGrouped) {

            $count = 0;
            foreach($usersGrouped as $org_name => $total) {
                if($item->name == $org_name){
                    $count = $total;
                }
            }

            return [
                'org_name' => $item->name,
                'count' => $count,
                'users_count' => $item->users_count,
                'percentage' => round(($count / $item->users_count ) * 100, 2)
            ];
        });

        return response()->json([
            'reportCompletion' => $data,
        ]);
    }
}
