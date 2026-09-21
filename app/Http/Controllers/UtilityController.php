<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBranch;
use App\Models\EventCategory;
use App\Models\EventGroup;
use DateTime;
use Log;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Artisan;

class UtilityController extends Controller
{
    public function __construct()
    {
        // 1. Pastikan user sudah login (jika belum, akan otomatis diarahkan ke halaman login)
        $this->middleware('auth');

        // 2. 🔒 Middleware untuk membatasi akses hanya untuk role superadmin
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Gunakan Nullsafe operator (?->) agar tidak error jika relasi role kosong
            $roleSlug = $user?->role?->slug ?? '';

            if ($roleSlug !== 'superadmin') {
                abort(403, 'Unauthorized. Hanya Superadmin yang diizinkan mengakses halaman ini.');
            }

            return $next($request);
        });
    }

    public function branchHierarkiMtq() {  
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
    
    public function testRun() { 
        return 'Run tested!'; 
    }

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

    public function health() { 
        // $user = auth()->user();
        // $roleSlug = optional($user->role)->slug ?? '';
        // return $roleSlug;

        // Note: Baris di bawah ini tidak akan pernah dieksekusi karena ada return $roleSlug di atasnya.
        
        $event = Event::first();
        return response()->json([
            'persiapan' => $event->isStageActive('persiapan'),
            'pendaftaran' => $event->isStageActive('pendaftaran'),
            'verifikasi I' => !$event->isStageActive('Verifikasi I'),
            'verifikasi II' => !$event->isStageActive('Verifikasi II')
        ]); 
        
    }

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

    function logoutAll(){
        \App\Models\User::each(function ($u) {
            Auth::login($u);
            Auth::logout();
        });

        return 'done';
    }

    /**
     * Menghitung total peserta terdaftar per wilayah untuk Event ID = 1
     */
    public function countParticipantsByRegion()
    {
        $eventId = 1;

        // 1. Cek ketersediaan event
        $event = \App\Models\Event::find($eventId);
        
        if (!$event) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Event dengan ID 1 tidak ditemukan.</h3>';
        }

        // 2. Query total peserta berdasarkan wilayah (contingent)
        $summaryCounts = \App\Models\EventParticipant::query()
            ->where('event_id', $eventId)
            ->select('contingent', \DB::raw('count(*) as total_peserta'))
            ->groupBy('contingent')
            ->orderBy('total_peserta', 'desc')
            ->get();

        // 3. Query detail peserta berdasarkan status pendaftaran per wilayah
        $detailedCounts = \App\Models\EventParticipant::query()
            ->where('event_id', $eventId)
            ->select('contingent', 'registration_status', \DB::raw('count(*) as total'))
            ->groupBy('contingent', 'registration_status')
            ->orderBy('contingent', 'asc')
            ->get()
            ->groupBy('contingent');

        // 4. Format hasil mapping detail
        $formattedDetails = $detailedCounts->map(function ($items) {
            $statusCounts = [];
            foreach ($items as $item) {
                $statusCounts[$item->registration_status] = $item->total;
            }
            return $statusCounts;
        });

        // 5. Render HTML Tabel
        $html = '<div style="font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; color: #333;">';
        
        // Header
        $html .= '<div style="text-align: center; margin-bottom: 25px;">';
        $html .= '<h2 style="color: #2c3e50; margin-bottom: 5px;">Statistik Peserta Terdaftar Berdasarkan Kafilah</h2>';
        $html .= '<h4 style="color: #7f8c8d; margin-top: 0;">Event: ' . e($event->event_name) . '</h4>';
        $html .= '</div>';

        // Mulai Tabel
        $html .= '<table style="width: 100%; border-collapse: collapse; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: #fff; border-radius: 8px; overflow: hidden;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #2980b9; color: #ffffff; text-align: left;">';
        $html .= '<th style="padding: 15px; width: 5%; text-align: center; border-right: 1px solid #3498db;">No</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #3498db;">Kafilah / Wilayah</th>';
        $html .= '<th style="padding: 15px; text-align: center; border-right: 1px solid #3498db;">Total Peserta</th>';
        $html .= '<th style="padding: 15px;">Rincian Status</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $grandTotal = 0; // Variabel untuk menyimpan total keseluruhan

        // Isi Data Tabel
        if ($summaryCounts->isEmpty()) {
            $html .= '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #7f8c8d; font-style: italic;">Belum ada data peserta yang terdaftar pada event ini.</td></tr>';
        } else {
            $no = 1;
            foreach ($summaryCounts as $summary) {
                $contingent = $summary->contingent ?: 'Tidak Diketahui';
                $details = isset($formattedDetails[$summary->contingent]) ? $formattedDetails[$summary->contingent] : [];
                $grandTotal += $summary->total_peserta; // Tambahkan ke grand total
                
                // Susun HTML untuk rincian status berupa badge/label kecil
                $statusHtml = '';
                foreach ($details as $status => $count) {
                    $bgColor = '#95a5a6';
                    if ($status === 'verified') $bgColor = '#27ae60';
                    elseif ($status === 'process') $bgColor = '#f39c12';
                    elseif ($status === 'need_revision') $bgColor = '#e67e22';
                    elseif ($status === 'rejected' || $status === 'disqualified') $bgColor = '#c0392b';
                    elseif ($status === 'bank_data') $bgColor = '#34495e';

                    $statusHtml .= '<span style="display: inline-block; background-color: ' . $bgColor . '; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: bold; margin: 2px 4px 2px 0;">' . e(strtoupper(str_replace('_', ' ', $status))) . ' : ' . $count . '</span>';
                }

                $rowBg = ($no % 2 === 0) ? '#f9fbfd' : '#ffffff';
                
                $html .= '<tr style="background-color: ' . $rowBg . '; border-bottom: 1px solid #ecf0f1;">';
                $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; color: #7f8c8d;">' . $no++ . '</td>';
                $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; font-weight: bold; color: #2c3e50;">' . e($contingent) . '</td>';
                $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; font-size: 1.2em; font-weight: bold; color: #2980b9;">' . $summary->total_peserta . '</td>';
                $html .= '<td style="padding: 12px 15px; line-height: 1.6;">' . ($statusHtml ?: '-') . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';

        // TFOOT untuk Grand Total
        $html .= '<tfoot>';
        $html .= '<tr style="background-color: #ecf0f1; border-top: 2px solid #bdc3c7;">';
        $html .= '<th colspan="2" style="padding: 15px; text-align: right; font-size: 1.1em; color: #2c3e50; border-right: 1px solid #bdc3c7;">TOTAL KESELURUHAN</th>';
        $html .= '<th style="padding: 15px; text-align: center; font-size: 1.3em; color: #c0392b; border-right: 1px solid #bdc3c7;">' . $grandTotal . '</th>';
        $html .= '<th></th>';
        $html .= '</tr>';
        $html .= '</tfoot>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Menghitung total peserta terdaftar per Cabang dan Golongan (Rincian Putra/Putri)
     */
    public function countParticipantsByBranchGroup()
    {
        $eventId = 1;

        // 1. Cek ketersediaan event
        $event = \App\Models\Event::find($eventId);
        
        if (!$event) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Event dengan ID 1 tidak ditemukan.</h3>';
        }

        // 2. Query total peserta
        $counts = \App\Models\EventParticipant::query()
            ->select('event_branch_id', 'event_group_id', 'event_category_id', \DB::raw('count(*) as total'))
            ->where('event_id', $eventId)
            ->groupBy('event_branch_id', 'event_group_id', 'event_category_id')
            ->get();

        // 3. Ambil data nama cabang, golongan, dan kategori
        $branchIds = $counts->pluck('event_branch_id')->unique();
        $groupIds = $counts->pluck('event_group_id')->unique();
        $categoryIds = $counts->pluck('event_category_id')->unique();

        $branches = \App\Models\EventBranch::whereIn('id', $branchIds)->get()->keyBy('id');
        $groups = \App\Models\EventGroup::whereIn('id', $groupIds)->get()->keyBy('id');
        $categories = \App\Models\EventCategory::whereIn('id', $categoryIds)->get()->keyBy('id');

        // 4. Susun data
        $groupedData = [];

        foreach ($counts as $row) {
            $bId = $row->event_branch_id;
            $gId = $row->event_group_id;
            $cId = $row->event_category_id;

            $key = $bId . '-' . $gId;

            if (!isset($groupedData[$key])) {
                $branch = $branches->get($bId);
                $group = $groups->get($gId);
                
                $groupedData[$key] = [
                    'branch_name' => $branch->full_name ?? $branch->branch_name ?? 'Cabang ID ' . $bId,
                    'group_name'  => $group->full_name ?? $group->group_name ?? 'Golongan ID ' . $gId,
                    'categories'  => [],
                    'total'       => 0,
                ];
            }

            $category = $categories->get($cId);
            $catName = $category->full_name ?? $category->category_name ?? 'Kategori ID ' . $cId;

            $groupedData[$key]['categories'][$catName] = $row->total;
            $groupedData[$key]['total'] += $row->total;
        }

        // Mengurutkan abjad
        usort($groupedData, function ($a, $b) {
            return strcmp($a['branch_name'], $b['branch_name']);
        });

        // 5. Render HTML Tabel
        $html = '<div style="font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; color: #333;">';
        
        $html .= '<div style="text-align: center; margin-bottom: 25px;">';
        $html .= '<h2 style="color: #2c3e50; margin-bottom: 5px;">Statistik Peserta Berdasarkan Cabang & Golongan</h2>';
        $html .= '<h4 style="color: #7f8c8d; margin-top: 0;">Event: ' . e($event->event_name) . '</h4>';
        $html .= '</div>';

        $html .= '<table style="width: 100%; border-collapse: collapse; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: #fff; border-radius: 8px; overflow: hidden;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #16a085; color: #ffffff; text-align: left;">';
        $html .= '<th style="padding: 15px; width: 5%; text-align: center; border-right: 1px solid #1abc9c;">No</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #1abc9c;">Cabang Lomba</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #1abc9c;">Golongan</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #1abc9c;">Rincian Kategori</th>';
        $html .= '<th style="padding: 15px; text-align: center;">Total</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $grandTotal = 0; // Variabel untuk menyimpan total keseluruhan

        if (empty($groupedData)) {
            $html .= '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #7f8c8d; font-style: italic;">Belum ada data peserta yang terdaftar pada cabang lomba.</td></tr>';
        } else {
            $no = 1;
            foreach ($groupedData as $data) {
                $grandTotal += $data['total']; // Tambahkan ke grand total

                $catHtml = '';
                foreach ($data['categories'] as $catName => $count) {
                    $upperCat = strtoupper($catName);
                    
                    $bgColor = '#95a5a6';
                    if (strpos($upperCat, 'PUTRA') !== false || strpos($upperCat, 'PRIA') !== false) {
                        $bgColor = '#2980b9';
                    } elseif (strpos($upperCat, 'PUTRI') !== false || strpos($upperCat, 'WANITA') !== false) {
                        $bgColor = '#8e44ad';
                    }
                    
                    $catHtml .= '<span style="display: inline-block; background-color: ' . $bgColor . '; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: bold; margin: 2px 4px 2px 0;">' . e($upperCat) . ' : ' . $count . '</span>';
                }

                $rowBg = ($no % 2 === 0) ? '#f9fbfd' : '#ffffff';
                
                $html .= '<tr style="background-color: ' . $rowBg . '; border-bottom: 1px solid #ecf0f1;">';
                $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; color: #7f8c8d;">' . $no++ . '</td>';
                $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; font-weight: bold; color: #2c3e50;">' . e($data['branch_name']) . '</td>';
                $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; color: #34495e;">' . e($data['group_name']) . '</td>';
                $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; line-height: 1.6;">' . $catHtml . '</td>';
                $html .= '<td style="padding: 12px 15px; text-align: center; font-size: 1.2em; font-weight: bold; color: #16a085;">' . $data['total'] . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        
        // TFOOT untuk Grand Total
        $html .= '<tfoot>';
        $html .= '<tr style="background-color: #ecf0f1; border-top: 2px solid #bdc3c7;">';
        $html .= '<th colspan="4" style="padding: 15px; text-align: right; font-size: 1.1em; color: #2c3e50; border-right: 1px solid #bdc3c7;">TOTAL KESELURUHAN PESERTA</th>';
        $html .= '<th style="padding: 15px; text-align: center; font-size: 1.3em; color: #c0392b;">' . $grandTotal . '</th>';
        $html .= '</tr>';
        $html .= '</tfoot>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function xUp(){
        Artisan::call('up');
        return 'Web Up';
    }

    public function xDown($view){
        Artisan::call('down', ['--secret' => 'devmode', '--render' => 'errors.' . $view]);

        return 'Web Down with command view: ' . $view;
    }

    public function viewError($view){
        return view('errors.' . $view);
    }
}