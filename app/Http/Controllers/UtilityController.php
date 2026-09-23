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

    /**
     * Menampilkan daftar seluruh peserta dari Event yang aktif (registration_status = process).
     * Dikelompokkan berdasarkan Region ID (dinamis), diurutkan dari Region ID terkecil.
     * Penomoran berjalan berlanjut tanpa reset per region.
     * Tampilan tabel: Nomor, Kontingen, Region ID, Nama Peserta, NIK, Cabang Golongan
     */
    public function activeEventParticipants()
    {
        // 1. Ambil event yang aktif.
        $activeEvent = \App\Models\Event::first(); 

        if (!$activeEvent) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Tidak ada event yang aktif.</h3>';
        }

        // 2. Tentukan kolom acuan Region ID berdasarkan event_level
        $participantColumn = 'regency_id'; // Default
        
        switch ($activeEvent->event_level) {
            case 'national':
                $participantColumn = 'province_id';
                break;
            case 'province':
                $participantColumn = 'regency_id';
                break;
            case 'regency':
                $participantColumn = 'district_id';
                break;
            case 'district':
                $participantColumn = 'village_id';
                break;
            default:
                abort(422, 'Event level tidak valid');
        }

        // 3. Ambil data event_participants beserta relasi participants dan eventCategory
        // Tambahkan orderBy('created_at', 'asc') agar data diurutkan berdasarkan waktu pembuatan dari yang terlama ke terbaru.
        $eventParticipants = \App\Models\EventParticipant::with(['participant', 'eventCategory'])
            ->where('event_id', $activeEvent->id)
            ->orderBy('event_group_id', 'asc') // <-- PERUBAHAN DI SINI
            ->get();

        // 4. Kelompokkan data dan urutkan berdasarkan Region ID terkecil
        // Karena collection di-grouping, urutan created_at di atas akan tetap dipertahankan di dalam masing-masing grup.
        $groupedParticipants = $eventParticipants->groupBy(function ($ep) use ($participantColumn) {
            // Gunakan angka besar 999999 sebagai fallback agar data null/kosong disortir paling bawah
            return $ep->participant?->{$participantColumn} ?? 999999;
        })->sortKeys(); 

        // 5. Render HTML Tabel
        $html = '<div style="font-family: Arial, sans-serif; max-width: 1300px; margin: 30px auto; color: #333;">';
        
        $html .= '<div style="text-align: center; margin-bottom: 25px;">';
        $html .= '<h2 style="color: #2c3e50; margin-bottom: 5px;">Daftar Peserta Terdaftar (Proses)</h2>';
        $html .= '<h4 style="color: #7f8c8d; margin-top: 0;">Event: ' . e($activeEvent->event_name ?? 'Aktif') . ' | Level: ' . e(strtoupper($activeEvent->event_level)) . '</h4>';
        $html .= '</div>';

        $html .= '<table style="width: 100%; border-collapse: collapse; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: #fff; border-radius: 8px; overflow: hidden;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #2c3e50; color: #ffffff; text-align: left;">';
        // 6 Kolom
        $html .= '<th style="padding: 15px; width: 5%; text-align: center; border-right: 1px solid #34495e;">Nomor</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #34495e;">Kontingen</th>';
        $html .= '<th style="padding: 15px; text-align: center; border-right: 1px solid #34495e;">Region ID</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #34495e;">Nama Peserta</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #34495e;">NIK</th>';
        $html .= '<th style="padding: 15px;">Cabang Golongan</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        if ($groupedParticipants->isEmpty()) {
            // Pastikan colspan menjadi 6 sesuai jumlah header
            $html .= '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #7f8c8d; font-style: italic;">Belum ada data peserta yang diproses pada event ini.</td></tr>';
        } else {
            
            // Inisialisasi variabel $no di LUAR loop region agar tidak ter-reset
            $no = 1; 
            
            // Loop per Kelompok Region
            foreach ($groupedParticipants as $rawRegionId => $participants) {
                
                // Ubah kembali key angka fallback menjadi string untuk ditampillkan
                $displayRegionId = ($rawRegionId === 999999) ? 'Tidak Diketahui' : $rawRegionId;

                // Baris Header untuk setiap Region Group
                $html .= '<tr style="background-color: #dcdde1; border-bottom: 2px solid #bdc3c7;">';
                // Pastikan colspan menjadi 6
                $html .= '<td colspan="6" style="padding: 10px 15px; font-weight: bold; color: #2c3e50; text-transform: uppercase;">';
                $html .= '📍 REGION ID: ' . e($displayRegionId) . ' <span style="float:right; color: #7f8c8d; font-size: 0.9em;">Total: ' . $participants->count() . ' Peserta</span>';
                $html .= '</td>';
                $html .= '</tr>';

                // Loop Detail Peserta di dalam Region tersebut (sudah berurutan sesuai created_at)
                foreach ($participants as $ep) {
                    $rowBg = ($no % 2 === 0) ? '#f9fbfd' : '#ffffff';
                    
                    // Ambil detail data
                    $kontingen = $ep->contingent ?: '-';
                    $nama = $ep->participant?->full_name ?? '-';
                    $nik = $ep->participant?->nik ?? '-';
                    
                    // Ambil Cabang Golongan dari eager load
                    $cabangGolongan = $ep->eventCategory?->full_name ?? '-';

                    $html .= '<tr style="background-color: ' . $rowBg . '; border-bottom: 1px solid #ecf0f1;">';
                    $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; color: #7f8c8d;">' . $no++ . '</td>';
                    $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; font-weight: bold; color: #2980b9;">' . e($kontingen) . '</td>';
                    $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; color: #34495e;">' . e($displayRegionId) . '</td>';
                    $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; font-weight: bold; color: #2c3e50;">' . e($nama) . '</td>';
                    $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; font-family: monospace; color: #2c3e50;">' . e($nik) . '</td>';
                    $html .= '<td style="padding: 12px 15px; font-weight: 500; color: #16a085;">' . e($cabangGolongan) . '</td>';
                    $html .= '</tr>';
                }
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
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
     * Menghitung total peserta terdaftar per wilayah untuk Event Aktif
     */
    public function countParticipantsByRegion()
    {
        // 1. Ambil event yang aktif.
        $event = \App\Models\Event::first(); 

        if (!$event) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Tidak ada event yang aktif.</h3>';
        }

        $eventId = $event->id;

        // 2. Tentukan kolom acuan Region ID berdasarkan event_level
        $participantColumn = 'regency_id'; // Default
        
        switch ($event->event_level) {
            case 'national':
                $participantColumn = 'province_id';
                break;
            case 'province':
                $participantColumn = 'regency_id';
                break;
            case 'regency':
                $participantColumn = 'district_id';
                break;
            case 'district':
                $participantColumn = 'village_id';
                break;
            default:
                // Fallback jika tidak ada level yang cocok
                $participantColumn = 'regency_id'; 
                break;
        }

        // 3. Query total peserta berdasarkan wilayah (contingent)
        // Lakukan JOIN ke tabel participants agar bisa mengambil kolom Region ID untuk di-order
        $summaryCounts = \App\Models\EventParticipant::query()
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->where('event_participants.event_id', $eventId)
            ->select(
                'event_participants.contingent', 
                "participants.{$participantColumn} as region_id",
                \DB::raw('count(event_participants.id) as total_peserta')
            )
            ->groupBy('event_participants.contingent', "participants.{$participantColumn}")
            ->orderBy('region_id', 'asc') // Urutkan berdasarkan Region ID (Terkecil ke Terbesar)
            ->get();

        // 4. Query detail peserta berdasarkan status pendaftaran per wilayah
        $detailedCounts = \App\Models\EventParticipant::query()
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->where('event_participants.event_id', $eventId)
            ->select(
                'event_participants.contingent', 
                "participants.{$participantColumn} as region_id",
                'event_participants.registration_status', 
                \DB::raw('count(event_participants.id) as total')
            )
            ->groupBy('event_participants.contingent', "participants.{$participantColumn}", 'event_participants.registration_status')
            ->get()
            ->groupBy('contingent');

        // 5. Format hasil mapping detail
        $formattedDetails = $detailedCounts->map(function ($items) {
            $statusCounts = [];
            foreach ($items as $item) {
                $statusCounts[$item->registration_status] = $item->total;
            }
            return $statusCounts;
        });

        // 6. Definisikan array status untuk memisahkan menjadi kolom-kolom
        $statusColumns = [
            'bank_data'      => 'Bank Data',
            'process'        => 'Proses',
            'need_revision'  => 'Revisi',
            'verified'       => 'Terverifikasi',
            'rejected'       => 'Ditolak',
            'disqualified'   => 'Mundur/Gugur'
        ];

        // 7. Render HTML Tabel
        $html = '<div style="font-family: Arial, sans-serif; max-width: 1200px; margin: 30px auto; color: #333;">';
        
        // Header
        $html .= '<div style="text-align: center; margin-bottom: 25px;">';
        $html .= '<h2 style="color: #2c3e50; margin-bottom: 5px;">Statistik Peserta Terdaftar Berdasarkan Kafilah</h2>';
        $html .= '<h4 style="color: #7f8c8d; margin-top: 0;">Event: ' . e($event->event_name) . ' | Level: ' . e(strtoupper($event->event_level)) . '</h4>';
        $html .= '</div>';

        // Mulai Tabel
        $html .= '<table style="width: 100%; border-collapse: collapse; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: #fff; border-radius: 8px; overflow: hidden;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #2980b9; color: #ffffff; text-align: center;">';
        $html .= '<th style="padding: 15px; width: 5%; border-right: 1px solid #3498db;">No</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #3498db; width: 10%;">Region ID</th>';
        $html .= '<th style="padding: 15px; border-right: 1px solid #3498db; text-align: left;">Kafilah / Wilayah</th>';
        
        // Render Header Kolom Status Dinamis
        foreach ($statusColumns as $label) {
            $html .= '<th style="padding: 15px; border-right: 1px solid #3498db;">' . $label . '</th>';
        }

        $html .= '<th style="padding: 15px;">Total Peserta</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $grandTotal = 0; 
        // Inisialisasi counter untuk Grand Total masing-masing status
        $grandTotalStatuses = array_fill_keys(array_keys($statusColumns), 0);

        // Isi Data Tabel
        if ($summaryCounts->isEmpty()) {
            $colspan = count($statusColumns) + 4; // No + Region ID + Kafilah + Statuses + Total
            $html .= '<tr><td colspan="' . $colspan . '" style="padding: 20px; text-align: center; color: #7f8c8d; font-style: italic;">Belum ada data peserta yang terdaftar pada event ini.</td></tr>';
        } else {
            $no = 1;
            foreach ($summaryCounts as $summary) {
                $contingent = $summary->contingent ?: 'Tidak Diketahui';
                $regionId = $summary->region_id ?: '-';
                $details = isset($formattedDetails[$summary->contingent]) ? $formattedDetails[$summary->contingent] : [];
                $grandTotal += $summary->total_peserta; 
                
                $rowBg = ($no % 2 === 0) ? '#f9fbfd' : '#ffffff';
                
                $html .= '<tr style="background-color: ' . $rowBg . '; border-bottom: 1px solid #ecf0f1;">';
                $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; color: #7f8c8d;">' . $no++ . '</td>';
                
                // Tambahkan Tampilan Kolom Region ID
                $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; font-weight: bold; color: #34495e;">' . e($regionId) . '</td>';
                $html .= '<td style="padding: 12px 15px; border-right: 1px solid #ecf0f1; font-weight: bold; color: #2c3e50;">' . e($contingent) . '</td>';
                
                // Loop untuk mengisi nilai tiap-tiap status (Bank Data, Proses, dll)
                foreach ($statusColumns as $statusKey => $label) {
                    $count = isset($details[$statusKey]) ? $details[$statusKey] : 0;
                    
                    // Tambahkan nilai ke Grand Total per Status
                    $grandTotalStatuses[$statusKey] += $count;
                    
                    // Beri warna angka jika lebih dari 0 agar mudah dilihat
                    $textColor = '#7f8c8d'; // abu-abu untuk 0
                    if ($count > 0) {
                        if ($statusKey === 'verified') $textColor = '#27ae60';
                        elseif ($statusKey === 'process') $textColor = '#f39c12';
                        elseif ($statusKey === 'need_revision') $textColor = '#e67e22';
                        elseif ($statusKey === 'rejected' || $statusKey === 'disqualified') $textColor = '#c0392b';
                        elseif ($statusKey === 'bank_data') $textColor = '#34495e';
                    }

                    $html .= '<td style="padding: 12px 15px; text-align: center; border-right: 1px solid #ecf0f1; font-weight: bold; font-size: 1.05em; color: ' . $textColor . ';">' . $count . '</td>';
                }

                $html .= '<td style="padding: 12px 15px; text-align: center; font-size: 1.2em; font-weight: bold; color: #2980b9;">' . $summary->total_peserta . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';

        // TFOOT untuk Grand Total Per Kolom
        $html .= '<tfoot>';
        $html .= '<tr style="background-color: #ecf0f1; border-top: 2px solid #bdc3c7;">';
        $html .= '<th colspan="3" style="padding: 15px; text-align: right; font-size: 1.1em; color: #2c3e50; border-right: 1px solid #bdc3c7;">TOTAL KESELURUHAN</th>';
        
        // Render Total Keseluruhan per Status
        foreach ($statusColumns as $statusKey => $label) {
            $html .= '<th style="padding: 15px; text-align: center; font-size: 1.1em; color: #2c3e50; border-right: 1px solid #bdc3c7;">' . $grandTotalStatuses[$statusKey] . '</th>';
        }

        $html .= '<th style="padding: 15px; text-align: center; font-size: 1.3em; color: #c0392b;">' . $grandTotal . '</th>';
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