<?php

namespace App\Http\Controllers;

use App\Models\EventBranch;
use App\Models\EventCategory;
use App\Models\EventGroup;
use DateTime;
Use Log;
use Illuminate\Http\Request;

class UtilityController extends Controller
{
    public function hierarkiMtq() {  
        // 1. Ambil semua data (jika untuk 1 event spesifik, tambahkan ->where('event_id', 1))
        $eventBranches = EventBranch::with('branch', 'event')->get();
        $eventGroups = EventGroup::with('group')->get();
        $eventCategories = EventCategory::with('category')->get();

        // 2. Kelompokkan Group berdasarkan event_id & branch_id
        $groupedGroups = $eventGroups->groupBy(function ($item) {
            return $item->event_id . '-' . $item->branch_id;
        });

        // 3. Kelompokkan Category berdasarkan event_id, branch_id, & group_id
        $groupedCategories = $eventCategories->groupBy(function ($item) {
            return $item->event_id . '-' . $item->branch_id . '-' . $item->group_id;
        });

        // 4. Render HTML
        $html = '<div style="font-family: sans-serif; line-height: 1.6;">';
        $html .= '<h2>Hierarki Event MTQ</h2>';
        $html .= '<ul>';
        
        foreach ($eventBranches as $branch) {
            $branchName = $branch->full_name ?? $branch->branch_name;
            $html .= '<li>';
            // Menambahkan prefix event_branch_id
            $html .= '<strong>Cabang:</strong> ' . $branchName . ' <span style="color: gray;">(event_branch_id: ' . $branch->id . ' | Event ID: ' . $branch->event_id . ')</span>';
            
            // Cari child groups dari collections yang sudah dikelompokkan
            $groupKey = $branch->event_id . '-' . $branch->branch_id;
            $groups = $groupedGroups->get($groupKey, collect());

            if ($groups->isNotEmpty()) {
                $html .= '<ul>';
                foreach ($groups as $group) {
                    $groupName = $group->full_name ?? $group->group_name;
                    $html .= '<li>';
                    // Menambahkan prefix event_group_id
                    $html .= '<strong>Golongan:</strong> ' . $groupName . ' <span style="color: gray;">(event_group_id: ' . $group->id . ')</span>';
                    
                    // Cari child categories dari collections yang sudah dikelompokkan
                    $categoryKey = $group->event_id . '-' . $group->branch_id . '-' . $group->group_id;
                    $categories = $groupedCategories->get($categoryKey, collect());

                    if ($categories->isNotEmpty()) {
                        $html .= '<ul>';
                        foreach ($categories as $category) {
                            $categoryName = $category->full_name ?? $category->category_name;
                            // Menambahkan prefix event_category_id
                            $html .= '<li><strong>Kategori:</strong> ' . $categoryName . ' <span style="color: gray;">(event_category_id: ' . $category->id . ')</span></li>';
                        }
                        $html .= '</ul>';
                    } else {
                        $html .= '<ul><li><em style="color: gray;">Tidak ada kategori</em></li></ul>';
                    }
                    
                    $html .= '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<ul><li><em style="color: gray;">Tidak ada golongan</em></li></ul>';
            }

            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
    
    public function testRun() { return 'Run tested!'; }

    public function testLog() { 
        Log::info('The logging system is functioning perfectly!');
        return 'Log written!';
    }

    public function rawLog() { 
        file_put_contents(
            storage_path('logs/raw.log'),
            "RAW OK\n",
            FILE_APPEND
        );
        return 'OK';
    }

    public function health() { return 'OK'; }

    public function logTest() { 
        \Log::error('WEB LOG OK');
        abort(500, 'TEST');
    }

    public function envCheck() {
        return response()->json([
            'env' => app()->environment(),
            'debug' => config('app.debug'),
            'log_channel' => config('logging.default'),
            'log_path' => storage_path('logs/laravel.log'),
        ]);
    }



    public function allUsers()
    {
        // Ambil data user, urutkan berdasarkan nama/nip_name
        $users = \App\Models\User::all()->sortBy('nip_name');

        $html = '<div style="font-family: sans-serif; padding: 20px;">';
        $html .= '<h2>Daftar Pengguna</h2>';
        $html .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #f2f2f2;">';
        $html .= '<th style="width: 50px; text-align: center;">No</th>';
        $html .= '<th>NIP / Nama</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $no = 1;
        foreach ($users as $user) {
            $html .= '<tr>';
            $html .= '<td style="text-align: center;">' . $no++ . '</td>';
            $html .= '<td>' . e($user->nip_name) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}
