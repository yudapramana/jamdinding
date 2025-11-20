<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrgReportController extends Controller
{
    public function index() {
        $monthYear = request()->query('monthyear');
        $orgId = request()->query('org_id');
        $userId = request()->query('user_id');

        return collect(
            [
                'data' => []
            ]
        );
    }
}
