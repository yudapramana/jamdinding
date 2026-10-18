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

        // 2. 🔒 Middleware untuk membatasi akses hanya untuk role superadmin dan admin_event
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Gunakan Nullsafe operator (?->) agar tidak error jika relasi role kosong
            $roleSlug = $user?->role?->slug ?? '';

            if (!in_array($roleSlug, ['superadmin', 'admin_event'])) {
                abort(403, 'Unauthorized. Hanya Superadmin dan Admin Event yang diizinkan mengakses halaman ini.');
            }

            return $next($request);
        });
    }

    /**
     * Menampilkan rekapitulasi jumlah peserta terverifikasi 
     * berdasarkan Utusan (Kontingen) dan Jenis Kelamin.
     */
    public function contingentGenderRecap()
    {
        // 1. Ambil event yang aktif.
        $activeEvent = \App\Models\Event::first(); 

        if (!$activeEvent) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Tidak ada event yang aktif.</h3>';
        }

        // 2. Tentukan kolom acuan Region ID untuk pengurutan berdasarkan event_level
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
        }

        // 3. Query rekapitulasi data dengan melakukan join ke tabel participants
        // untuk membaca nilai gender dan region_id.
        $summary = \App\Models\EventParticipant::query()
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->where('event_participants.event_id', $activeEvent->id)
            ->where('event_participants.registration_status', 'verified') // Hanya hitung yang verified
            ->select(
                'event_participants.contingent',
                "participants.{$participantColumn} as region_id",
                \DB::raw("SUM(CASE WHEN participants.gender = 'MALE' THEN 1 ELSE 0 END) as male_count"),
                \DB::raw("SUM(CASE WHEN participants.gender = 'FEMALE' THEN 1 ELSE 0 END) as female_count"),
                \DB::raw("COUNT(event_participants.id) as total")
            )
            ->groupBy('event_participants.contingent', "participants.{$participantColumn}")
            ->orderBy('region_id', 'asc') // Urutkan berdasarkan ID wilayah agar urutan daerah sesuai hierarki
            ->get();

        // 4. Render HTML Tabel menyerupai gambar
        $html = '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; color: #000;">';
        
        $html .= '<table style="width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 14px;">';
        $html .= '<thead>';
        
        // Baris Header 1
        $html .= '<tr>';
        $html .= '<th rowspan="2" style="border: 1px solid #000; padding: 10px; width: 5%; text-align: center;">NO</th>';
        $html .= '<th rowspan="2" style="border: 1px solid #000; padding: 10px; text-align: center;">UTUSAN</th>';
        $html .= '<th colspan="2" style="border: 1px solid #000; padding: 10px; text-align: center;">PESERTA</th>';
        $html .= '<th rowspan="2" style="border: 1px solid #000; padding: 10px; text-align: center; width: 15%;">JUMLAH</th>';
        $html .= '</tr>';

        // Baris Header 2 (Sub-header PESERTA)
        $html .= '<tr>';
        $html .= '<th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">LAKI-LAKI</th>';
        $html .= '<th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">PEREMPUAN</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $totalMale = 0;
        $totalFemale = 0;
        $grandTotal = 0;

        if ($summary->isEmpty()) {
            $html .= '<tr><td colspan="5" style="border: 1px solid #000; padding: 15px; text-align: center; font-style: italic;">Belum ada data peserta terverifikasi pada event ini.</td></tr>';
        } else {
            $no = 1;
            foreach ($summary as $row) {
                $contingent = $row->contingent ?: '-';
                
                // Tambahkan ke total akumulasi terbawah
                $totalMale += $row->male_count;
                $totalFemale += $row->female_count;
                $grandTotal += $row->total;

                $html .= '<tr>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $no++ . '</td>';
                // Utusan rata kiri sesuai gambar
                $html .= '<td style="border: 1px solid #000; padding: 8px 15px; text-align: left;">' . e(strtoupper($contingent)) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $row->male_count . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $row->female_count . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $row->total . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        
        // Baris Footer (Jumlah Keseluruhan)
        $html .= '<tfoot>';
        $html .= '<tr>';
        $html .= '<th colspan="2" style="border: 1px solid #000; padding: 10px; text-align: center;">JUMLAH</th>';
        $html .= '<th style="border: 1px solid #000; padding: 10px; text-align: center;">' . $totalMale . '</th>';
        $html .= '<th style="border: 1px solid #000; padding: 10px; text-align: center;">' . $totalFemale . '</th>';
        $html .= '<th style="border: 1px solid #000; padding: 10px; text-align: center;">' . $grandTotal . '</th>';
        $html .= '</tr>';
        $html .= '</tfoot>';
        
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Menampilkan daftar peserta dengan status terverifikasi (verified)
     * Format tabel disesuaikan dengan format SK/Penetapan.
     */
    public function verifiedParticipants()
    {
        // 1. Ambil event yang aktif.
        $activeEvent = \App\Models\Event::first(); 

        if (!$activeEvent) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Tidak ada event yang aktif.</h3>';
        }

        // 2. Ambil data event_participants yang statusnya 'verified'
        $participants = \App\Models\EventParticipant::with(['participant', 'eventGroup'])
            ->where('event_id', $activeEvent->id)
            ->where('registration_status', 'verified')
            ->orderBy('event_branch_id', 'asc')
            ->orderBy('participant_number', 'asc')
            ->get();

        // 3. Render HTML Tabel menyesuaikan desain gambar
        $html = '<div style="font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; color: #000;">';
        
        // Bagian Header Judul
        $html .= '<div style="text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 14px; line-height: 1.5;">';
        $html .= 'PENETAPAN SEBAGAI PESERTA<br>';
        $html .= 'PADA ' . strtoupper($activeEvent->event_name ?? 'MTQ') . ' TAHUN ' . ($activeEvent->event_year ?? date('Y'));
        $html .= '</div>';

        // Bagian Tabel
        $html .= '<table style="width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 13px;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #92d050; border-bottom: 2px solid #000;">';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center; width: 5%;">NO.<br>URUT</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">NO.<br>PESERTA</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: left;">NAMA PESERTA</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">CABANG</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">GOLONGAN</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">KATEGORIN</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">KAFILAH</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        if ($participants->isEmpty()) {
            $html .= '<tr><td colspan="7" style="border: 1px solid #000; padding: 15px; text-align: center; font-style: italic;">Belum ada peserta yang terverifikasi pada event ini.</td></tr>';
        } else {
            $no = 1;
            foreach ($participants as $ep) {
                $noPeserta = substr($ep->participant_number, 0, 8);
                $nama = $ep->participant?->full_name ?? '-';
                
                // Ambil string cabang asli
                $cabangRaw = $ep->eventGroup?->branch_name ?? '-';
                $golongan = $ep->eventGroup?->group_name ?? '-';
                
                // Filter: Jika ada teks di dalam kurung, ambil yang di dalam kurung saja
                if (preg_match('/\((.*?)\)/', $cabangRaw, $matches)) {
                    $cabangFinal = trim($matches[1]);
                } else {
                    $cabangFinal = trim($cabangRaw);
                }
                
                $jk = '-';
                if ($ep->participant?->gender === 'MALE') {
                    $jk = 'Putra';
                } elseif ($ep->participant?->gender === 'FEMALE') {
                    $jk = 'Putri';
                }
                
                $kafilah = $ep->contingent ?: '-';

                $html .= '<tr>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . $no++ . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($noPeserta) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px;">' . e(strtoupper($nama)) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e(ucwords(strtolower($cabangFinal))) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e(ucwords(strtolower($golongan))) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($jk) . '</td>';
                // ✅ PERBAIKAN: Menghapus strtolower() agar format teks kafilah tetap sesuai aslinya
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($kafilah) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Menampilkan daftar peserta dengan status terverifikasi (verified)
     * Format tabel disesuaikan dengan format SK/Penetapan.
     * Secara spesifik diurutkan berdasarkan Region ID (Wilayah) terlebih dahulu, lalu cabang.
     */
    public function verifiedParticipantSortByRegion()
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
        }

        // 3. Ambil data event_participants yang statusnya 'verified'
        // Lakukan JOIN dengan tabel participants agar bisa melakukan orderBy pada kolom region_id
        $participants = \App\Models\EventParticipant::with(['participant', 'eventGroup'])
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->select('event_participants.*', "participants.{$participantColumn} as region_id")
            ->where('event_participants.event_id', $activeEvent->id)
            ->where('event_participants.registration_status', 'verified')
            // Diurutkan berdasarkan region_id dahulu, baru cabang dan nomor peserta
            ->orderBy('region_id', 'asc')
            ->orderBy('event_participants.event_branch_id', 'asc')
            ->orderBy('event_participants.participant_number', 'asc')
            ->get();

        // 4. Render HTML Tabel
        $html = '<div style="font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; color: #000;">';
        
        // Bagian Header Judul (Sederhana)
        $html .= '<div style="text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 14px; line-height: 1.5;">';
        $html .= 'PENETAPAN PESERTA<br>';
        $html .= strtoupper($activeEvent->event_name ?? 'MUSABAQAH TILAWATIL QURAN') . ' TAHUN ' . ($activeEvent->event_year ?? date('Y'));
        $html .= '</div>';

        // Bagian Tabel
        $html .= '<table style="width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 13px;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #92d050; border-bottom: 2px solid #000;">';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center; width: 5%;">NO.<br>URUT</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">NO.<br>PESERTA</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: left;">NAMA PESERTA</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">CABANG</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">GOLONGAN</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">KATEGORIN</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">KAFILAH</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        if ($participants->isEmpty()) {
            $html .= '<tr><td colspan="7" style="border: 1px solid #000; padding: 15px; text-align: center; font-style: italic;">Belum ada peserta yang terverifikasi pada event ini.</td></tr>';
        } else {
            $no = 1;
            foreach ($participants as $ep) {
                $noPeserta = substr($ep->participant_number, 0, 8);
                $nama = $ep->participant?->full_name ?? '-';
                
                // Ambil string cabang asli
                $cabangRaw = $ep->eventGroup?->branch_name ?? '-';
                $golongan = $ep->eventGroup?->group_name ?? '-';
                
                // Filter: Jika ada teks di dalam kurung, ambil yang di dalam kurung saja
                if (preg_match('/\((.*?)\)/', $cabangRaw, $matches)) {
                    $cabangFinal = trim($matches[1]);
                } else {
                    $cabangFinal = trim($cabangRaw);
                }
                
                $jk = '-';
                if ($ep->participant?->gender === 'MALE') {
                    $jk = 'Putra';
                } elseif ($ep->participant?->gender === 'FEMALE') {
                    $jk = 'Putri';
                }
                
                $kafilah = $ep->contingent ?: '-';

                $html .= '<tr>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . $no++ . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($noPeserta) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px;">' . e(strtoupper($nama)) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e(ucwords(strtolower($cabangFinal))) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e(ucwords(strtolower($golongan))) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($jk) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($kafilah) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Menampilkan rekapitulasi jumlah peserta terverifikasi 
     * berdasarkan Cabang Golongan (Event Group) dan Jenis Kelamin.
     */
    public function groupGenderRecap()
    {
        // 1. Ambil event yang aktif.
        $activeEvent = \App\Models\Event::first(); 

        if (!$activeEvent) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Tidak ada event yang aktif.</h3>';
        }

        // 2. Query rekapitulasi data dengan melakukan join ke tabel participants dan event_groups
        $summary = \App\Models\EventParticipant::query()
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->join('event_groups', 'event_participants.event_group_id', '=', 'event_groups.id')
            ->where('event_participants.event_id', $activeEvent->id)
            ->where('event_participants.registration_status', 'verified') // Hanya hitung yang verified
            ->select(
                'event_groups.full_name as group_name',
                \DB::raw("SUM(CASE WHEN participants.gender = 'MALE' THEN 1 ELSE 0 END) as male_count"),
                \DB::raw("SUM(CASE WHEN participants.gender = 'FEMALE' THEN 1 ELSE 0 END) as female_count"),
                \DB::raw("COUNT(event_participants.id) as total")
            )
            // Grouping dan pengurutan berdasarkan hierarki cabang dan golongan
            ->groupBy('event_groups.id', 'event_groups.full_name', 'event_groups.order_number', 'event_groups.branch_id')
            ->orderBy('event_groups.branch_id', 'asc')
            ->orderBy('event_groups.order_number', 'asc')
            ->get();

        // 3. Render HTML Tabel menyerupai gambar
        $html = '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; color: #000;">';
        
        $html .= '<table style="width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 14px;">';
        $html .= '<thead>';
        
        // Baris Header 1
        $html .= '<tr>';
        $html .= '<th rowspan="2" style="border: 1px solid #000; padding: 10px; width: 5%; text-align: center;">NO</th>';
        // Header mengikuti format gambar (tertulis UTUSAN meskipun isinya Cabang)
        $html .= '<th rowspan="2" style="border: 1px solid #000; padding: 10px; text-align: center;">UTUSAN</th>';
        $html .= '<th colspan="2" style="border: 1px solid #000; padding: 10px; text-align: center;">PESERTA</th>';
        $html .= '<th rowspan="2" style="border: 1px solid #000; padding: 10px; text-align: center; width: 15%;">JUMLAH</th>';
        $html .= '</tr>';

        // Baris Header 2 (Sub-header PESERTA)
        $html .= '<tr>';
        $html .= '<th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">LAKI-LAKI</th>';
        $html .= '<th style="border: 1px solid #000; padding: 8px; text-align: center; width: 15%;">PEREMPUAN</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $totalMale = 0;
        $totalFemale = 0;
        $grandTotal = 0;

        if ($summary->isEmpty()) {
            $html .= '<tr><td colspan="5" style="border: 1px solid #000; padding: 15px; text-align: center; font-style: italic;">Belum ada data peserta terverifikasi pada event ini.</td></tr>';
        } else {
            $no = 1;
            foreach ($summary as $row) {
                $groupName = $row->group_name ?: '-';
                
                // Tambahkan ke total akumulasi terbawah
                $totalMale += $row->male_count;
                $totalFemale += $row->female_count;
                $grandTotal += $row->total;

                $html .= '<tr>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $no++ . '</td>';
                // Cabang rata kiri sesuai gambar
                $html .= '<td style="border: 1px solid #000; padding: 8px 15px; text-align: left;">' . e(strtoupper($groupName)) . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $row->male_count . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $row->female_count . '</td>';
                $html .= '<td style="border: 1px solid #000; padding: 8px; text-align: center;">' . $row->total . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        
        // Baris Footer (Jumlah Keseluruhan) - Mengikuti gambar tanpa teks "JUMLAH" di kolom pertama, tapi digabung
        $html .= '<tfoot>';
        $html .= '<tr>';
        $html .= '<th colspan="2" style="border: 1px solid #000; padding: 10px; text-align: center;">JUMLAH</th>';
        $html .= '<th style="border: 1px solid #000; padding: 10px; text-align: center;">' . $totalMale . '</th>';
        $html .= '<th style="border: 1px solid #000; padding: 10px; text-align: center;">' . $totalFemale . '</th>';
        $html .= '<th style="border: 1px solid #000; padding: 10px; text-align: center;">' . $grandTotal . '</th>';
        $html .= '</tr>';
        $html .= '</tfoot>';
        
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Menampilkan daftar peserta dengan status terverifikasi (verified)
     * Format tabel disesuaikan dengan format SK/Penetapan.
     * Secara spesifik diurutkan berdasarkan kontingen (kafilah) terlebih dahulu, lalu cabang.
     */
    public function verifiedParticipantSortBycontingent()
    {
        // 1. Ambil event yang aktif.
        $activeEvent = \App\Models\Event::first(); 

        if (!$activeEvent) {
            return '<h3 style="color: red; text-align: center; font-family: sans-serif; margin-top: 50px;">Tidak ada event yang aktif.</h3>';
        }

        // 2. Ambil data event_participants yang statusnya 'verified'
        $participants = \App\Models\EventParticipant::with(['participant', 'eventGroup'])
            ->where('event_id', $activeEvent->id)
            ->where('registration_status', 'verified')
            // Diurutkan berdasarkan contingent dahulu, baru cabang dan nomor peserta
            ->orderBy('contingent', 'asc')
            ->orderBy('event_branch_id', 'asc')
            ->orderBy('participant_number', 'asc')
            ->get();

        // 3. Render HTML Tabel
        $html = '<div style="font-family: Arial, sans-serif; max-width: 1000px; margin: 30px auto; color: #000;">';
        
        // Bagian Header Judul (Sederhana)
        $html .= '<div style="text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 14px; line-height: 1.5;">';
        $html .= 'PENETAPAN PESERTA<br>';
        $html .= strtoupper($activeEvent->event_name ?? 'MUSABAQAH TILAWATIL QURAN') . ' TAHUN ' . ($activeEvent->event_year ?? date('Y'));
        $html .= '</div>';

        // Bagian Tabel
        $html .= '<table style="width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 13px;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #92d050; border-bottom: 2px solid #000;">';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center; width: 5%;">NO.<br>URUT</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">NO.<br>PESERTA</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: left;">NAMA PESERTA</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">CABANG</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">GOLONGAN</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">KATEGORIN</th>';
        $html .= '<th style="border: 2px solid #000; padding: 8px; text-align: center;">KAFILAH</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        if ($participants->isEmpty()) {
            $html .= '<tr><td colspan="7" style="border: 1px solid #000; padding: 15px; text-align: center; font-style: italic;">Belum ada peserta yang terverifikasi pada event ini.</td></tr>';
        } else {
            $no = 1;
            foreach ($participants as $ep) {
                $noPeserta = substr($ep->participant_number, 0, 8);
                $nama = $ep->participant?->full_name ?? '-';
                
                // Ambil string cabang asli
                $cabangRaw = $ep->eventGroup?->branch_name ?? '-';
                $golongan = $ep->eventGroup?->group_name ?? '-';
                
                // Filter: Jika ada teks di dalam kurung, ambil yang di dalam kurung saja
                if (preg_match('/\((.*?)\)/', $cabangRaw, $matches)) {
                    $cabangFinal = trim($matches[1]);
                } else {
                    $cabangFinal = trim($cabangRaw);
                }
                
                $jk = '-';
                if ($ep->participant?->gender === 'MALE') {
                    $jk = 'Putra';
                } elseif ($ep->participant?->gender === 'FEMALE') {
                    $jk = 'Putri';
                }
                
                $kafilah = $ep->contingent ?: '-';

                $html .= '<tr>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . $no++ . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($noPeserta) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px;">' . e(strtoupper($nama)) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e(ucwords(strtolower($cabangFinal))) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e(ucwords(strtolower($golongan))) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($jk) . '</td>';
                $html .= '<td style="border: 2px solid #000; padding: 6px 8px; text-align: center;">' . e($kafilah) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Mengisi participant_number awal (BranchCode.ChestNumber) 
     * untuk seluruh peserta individu yang sudah terverifikasi.
     */
    public function generateChestNumbers()
    {
        // Ambil event yang aktif
        $activeEvent = \App\Models\Event::first(); 

        if (!$activeEvent) {
            return response()->json(['message' => 'Tidak ada event aktif.'], 404);
        }

        // Ambil peserta yang sudah diverifikasi pendaftarannya
        $participants = \App\Models\EventParticipant::with(['participant', 'eventGroup'])
            ->where('event_id', $activeEvent->id)
            ->where('registration_status', 'verified')
            ->get();

        // Array Urutan Kontingen Kecamatan
        $districts = [
            "IV Jurai", 
            "Bayang", 
            "IV Nagari Bayang Utara", 
            "Batang Kapas", 
            "Koto XI Tarusan", 
            "Sutera", 
            "Lengayang", 
            "Ranah Pesisir", 
            "Linggo Sari Baganti", 
            "Airpura", 
            "Pancung Soal", 
            "Basa Ampek Balai Tapan", 
            "Ranah Ampek Hulu Tapan", 
            "Lunang", 
            "Silaut"
        ];

        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($participants as $ep) {
            // Lewati jika peserta adalah tim (cabang beregu)
            if ($ep->eventGroup && $ep->eventGroup->is_team) {
                $skippedCount++;
                continue;
            }

            // Validasi data participant
            if (!$ep->participant) {
                $errors[] = "Peserta dengan EventParticipant ID {$ep->id} tidak memiliki data master relasi.";
                $skippedCount++;
                continue;
            }

            $gender = strtoupper($ep->participant->gender);
            $contingentName = trim($ep->contingent);
            $districtIndex = false;

            // Cari index berdasarkan nama kontingen (case-insensitive)
            foreach ($districts as $index => $district) {
                if (strcasecmp($district, $contingentName) === 0) {
                    $districtIndex = $index;
                    break;
                }
            }

            if ($districtIndex === false) {
                $errors[] = "Kontingen '{$contingentName}' tidak dikenali (ID: {$ep->id}).";
                $skippedCount++;
                continue;
            }

            /**
             * Logika Chest Number:
             * Index 0 (IV Jurai) -> Putri = 1, Putra = 2
             * Index 1 (Bayang) -> Putri = 3, Putra = 4
             * Rumus: (Index * 2) + (Gender === 'FEMALE' ? 1 : 2)
             */
            $chestNumber = ($districtIndex * 2) + ($gender === 'FEMALE' ? 1 : 2);
            $branchCode = $ep->eventGroup->code;

            // Format: BranchCode.ChestNumber (Contoh: TL.04.01)
            $participantNumber = sprintf('%s.%02d', $branchCode, $chestNumber);

            // Update ke database
            $ep->update([
                'branch_code' => $branchCode,
                'participant_number' => $participantNumber,
            ]);

            $updatedCount++;
        }

        return response()->json([
            'message' => 'Generate awal Chest Number selesai.',
            'event_id' => $activeEvent->id,
            'summary' => [
                'updated_participants' => $updatedCount,
                'skipped_teams_or_invalid' => $skippedCount,
            ],
            'errors' => $errors
        ]);
    }

    /**
     * Mengisi participant_number awal (BranchCode.ChestNumber) 
     * untuk seluruh tim (EventTeam) yang valid, beserta seluruh 
     * anggota tim di tabel event_participants.
     */
    public function generateTeamChestNumbers()
    {
        $activeEvent = \App\Models\Event::first(); 

        if (!$activeEvent) {
            return response()->json(['message' => 'Tidak ada event aktif.'], 404);
        }

        // Ambil tim yang berada di event aktif dan memiliki minimal 1 anggota terverifikasi
        $teams = \App\Models\EventTeam::with(['eventCategory', 'eventGroup'])
            ->where('event_id', $activeEvent->id)
            ->whereHas('participants', function($q) {
                $q->where('registration_status', 'verified');
            })
            ->get();

        $districts = [
            "IV Jurai", 
            "Bayang", 
            "IV Nagari Bayang Utara", 
            "Batang Kapas", 
            "Koto XI Tarusan", 
            "Sutera", 
            "Lengayang", 
            "Ranah Pesisir", 
            "Linggo Sari Baganti", 
            "Airpura", 
            "Pancung Soal", 
            "Basa Ampek Balai Tapan", 
            "Ranah Ampek Hulu Tapan", 
            "Lunang", 
            "Silaut"
        ];

        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($teams as $team) {
            // Validasi keberadaan relasi master
            if (!$team->eventCategory || !$team->eventGroup) {
                $errors[] = "Tim ID {$team->id} tidak memiliki relasi kategori atau golongan.";
                $skippedCount++;
                continue;
            }

            $categoryName = strtoupper($team->eventCategory->category_name ?? '');
            
            // Validasi gender berdasarkan nama kategori
            if (!in_array($categoryName, ['PUTRA', 'PUTRI'])) {
                $errors[] = "Tim ID {$team->id} memiliki kategori yang bukan Putra/Putri ({$categoryName}).";
                $skippedCount++;
                continue;
            }
            
            $isFemale = ($categoryName === 'PUTRI');
            $contingentName = trim($team->contingent);
            $districtIndex = false;

            // Cari index berdasarkan nama kontingen (case-insensitive)
            foreach ($districts as $index => $district) {
                if (strcasecmp($district, $contingentName) === 0) {
                    $districtIndex = $index;
                    break;
                }
            }

            if ($districtIndex === false) {
                $errors[] = "Kontingen '{$contingentName}' tidak dikenali (Tim ID: {$team->id}).";
                $skippedCount++;
                continue;
            }

            // Hitung Chest Number (Perbaikan operator perkalian pada index)
            $chestNumber = ($districtIndex * 2) + ($isFemale ? 1 : 2);
            $branchCode = $team->eventGroup->code;

            // Format Awal: BranchCode.ChestNumber (Contoh: MSQ.02)
            $participantNumber = sprintf('%s.%02d', $branchCode, $chestNumber);

            // 1. Update ke tabel event_teams
            $team->update([
                'branch_code' => $branchCode,
                'participant_number' => $participantNumber,
            ]);

            // 2. Update massal ke tabel event_participants yang tergabung di dalam tim ini
            \App\Models\EventParticipant::where('event_team_id', $team->id)->update([
                'branch_code' => $branchCode,
                'participant_number' => $participantNumber,
            ]);

            $updatedCount++;
        }

        return response()->json([
            'message' => 'Generate awal Chest Number untuk Tim dan Anggotanya selesai.',
            'event_id' => $activeEvent->id,
            'summary' => [
                'updated_teams' => $updatedCount,
                'skipped_teams' => $skippedCount,
            ],
            'errors' => $errors
        ]);
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
            ->where('registration_status', 'verified')
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

    /**
     * Memasukkan data Dewan Hakim (Aman dari duplikat & mengosongkan panel member di awal)
     */
    public function seedDewanHakim()
    {
        // 1. Ambil event yang aktif (Atau default 1 jika belum ada)
        $activeEvent = \App\Models\Event::first(); 
        $eventId = $activeEvent ? $activeEvent->id : 1;
        
        $faker = \Faker\Factory::create('id_ID');

        // Data JSON Majelis & Dewan Hakim
        $dataMajelis = [
            [
                "id" => "I", "nama_majelis" => "Tilawah Dewasa dan Remaja",
                "dewan_hakim" => [
                    ["nomor" => 5, "nama" => "Amrizal Jamain, S.H.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 6, "nama" => "Dr. H. Ahmad Kosasih, Μ.Α.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 7, "nama" => "Dra. Hj. Yusni Ernawati", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 8, "nama" => "H. Iklanedi, S.Kom.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 9, "nama" => "H. Refri, M.Pd.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 10, "nama" => "Drs. Arjulis Imam Mulia", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 11, "nama" => "Hj. Nuralisasni, S.Pd.I.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 12, "nama" => "Ikang Fauzi, S.Pd.I.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "II", "nama_majelis" => "Tilawah Anak-Anak, TK dan Tuna Netra",
                "dewan_hakim" => [
                    ["nomor" => 13, "nama" => "H. Alfion, S.Ag.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 14, "nama" => "Juliadi, S.H.I.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 15, "nama" => "H. Firdaus", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 16, "nama" => "Zainal Munaf, S.Pd.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 17, "nama" => "Jon Harmen, M.Α.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 18, "nama" => "Sri Manengsih, S.Pd.I.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "III", "nama_majelis" => "Hafiz 1 dan 5 Juz Non Tilawah 10Juz",
                "dewan_hakim" => [
                    ["nomor" => 19, "nama" => "Indra Hadi, S.M.I.Q.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 20, "nama" => "H. DediAfdal, Lc., M.Ud.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 21, "nama" => "Syahrial, S.IQ., S.Pd.I.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "IV", "nama_majelis" => "Hafiz 1 dan 5 Juz Tilawah",
                "dewan_hakim" => [
                    ["nomor" => 22, "nama" => "Irsyad, S.M.I.Q.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 23, "nama" => "Bambang HeriantoLc, .", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 24, "nama" => "Wilya Eka Putra, S.H.I.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "V", "nama_majelis" => "Kitab Standar",
                "dewan_hakim" => [
                    ["nomor" => 25, "nama" => "Joni Efendi, Lc., M.Α.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 26, "nama" => "Masril, M. Ag.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 27, "nama" => "Ahmad Daroji, S.Ag., M.Pd.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "VI", "nama_majelis" => "Tafsir",
                "dewan_hakim" => [
                    ["nomor" => 28, "nama" => "Prof. Dr. H. Rusdy AM, M.A.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 29, "nama" => "Erna Yanti, M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 30, "nama" => "Syafriadi B, S.H.I.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 31, "nama" => "Dr. Heri Surikno, Μ.Α.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 32, "nama" => "Darwati, SMIQ", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 33, "nama" => "Dr.Halimah, M.A.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "VII", "nama_majelis" => "Tartil Dasar, Menengah dan Umum",
                "dewan_hakim" => [
                    ["nomor" => 34, "nama" => "Dr. Yusran Lubis, S. Ag., M.Pd.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 35, "nama" => "Yunani", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 36, "nama" => "Zaimal Elpetani", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 37, "nama" => "Nasrizal Rici", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 38, "nama" => "Jon Rusdi, S.Pd.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 39, "nama" => "Ridwan, S.E.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "VIII", "nama_majelis" => "Khutbah Jum'at dan Azan",
                "dewan_hakim" => [
                    ["nomor" => 40, "nama" => "Drs. Damri Tanjung, M.A.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 41, "nama" => "Rahmi Zulia, S.H.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 42, "nama" => "H. Sudirman, S.Ag.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 43, "nama" => "Pen Andri, S.H.I.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 44, "nama" => "Dodon Hardiman, M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 45, "nama" => "Makmur, S.Ag., M.Pd.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "IX", "nama_majelis" => "Fahmil Qur'an",
                "dewan_hakim" => [
                    ["nomor" => 46, "nama" => "Muslim, M.Ag.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 47, "nama" => "Johardi, S.Ag., M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 48, "nama" => "Dr. H. Depi Dasmal, M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 49, "nama" => "Amrina Rosyada, S.Th.I.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "X", "nama_majelis" => "Syarhil Qur'an",
                "dewan_hakim" => [
                    ["nomor" => 50, "nama" => "Drs. H.Dasril Ilyas.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 51, "nama" => "Rita Gamasari, S.Ag., M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 52, "nama" => "Zidni Ilman Nafian, M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 53, "nama" => "Endrizal, S.Ag., M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 54, "nama" => "Wawan Gunawan, S.Pd.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 55, "nama" => "Nafnaldi, S.H.I.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "XI", "nama_majelis" => "KTIQ dan KTIH",
                "dewan_hakim" => [
                    ["nomor" => 56, "nama" => "Dr. Faisal, M.Ag.", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 57, "nama" => "Dr. Rika Sasralina, M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 58, "nama" => "Nofri Yendra, S.H.I., Μ.Α.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 59, "nama" => "Akhiyen Nuardi, M.Pd.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 60, "nama" => "Suhardi Nasution, M.Pd.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 61, "nama" => "Hengki Zar. S.IQ., M.Pd.", "jabatan" => "Hakim Anggota"]
                ]
            ],
            [
                "id" => "XII", "nama_majelis" => "Kaligrafi",
                "dewan_hakim" => [
                    ["nomor" => 62, "nama" => "Dr. Ade Setiawan", "jabatan" => "Ketua Bidang"],
                    ["nomor" => 63, "nama" => "Dr. Ardi Satria, M.A.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 64, "nama" => "Aliman, S.Pd.I.", "jabatan" => "Hakim Anggota"],
                    ["nomor" => 65, "nama" => "Ali Mardona, A.Ma.", "jabatan" => "Hakim Anggota"]
                ]
            ]
        ];

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Hapus semua isi dari event_judge_panel_members khusus untuk event yang aktif
            // Ini untuk menghindari duplikasi saat di-run ulang dan memastikan data fresh
            $activePanelIds = \Illuminate\Support\Facades\DB::table('event_judge_panels')
                ->where('event_id', $eventId)
                ->pluck('id');
                
            if ($activePanelIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('event_judge_panel_members')
                    ->whereIn('event_judge_panel_id', $activePanelIds)
                    ->delete();
            }

            $insertedJudges = 0;
            $skippedJudges = 0;

            foreach ($dataMajelis as $index => $majelis) {
                // 1. Cari Event Judge Panel berdasarkan Kode
                $panelCode = 'MJL-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                
                $existingPanel = \Illuminate\Support\Facades\DB::table('event_judge_panels')
                    ->where('event_id', $eventId)
                    ->where('code', $panelCode)
                    ->first();

                // Jika panel (majelis) tidak ditemukan, lewati majelis ini
                if (!$existingPanel) {
                    continue; 
                }
                
                $panelId = $existingPanel->id;

                foreach ($majelis['dewan_hakim'] as $order => $hakim) {
                    
                    // 2. Cek & Insert Master Judges (Berdasarkan Nama)
                    $existingMasterJudge = \Illuminate\Support\Facades\DB::table('master_judges')
                        ->where('full_name', $hakim['nama'])
                        ->first();

                    if (!$existingMasterJudge) {
                        // Generate NIK yang pasti unik untuk dipakai di tabel users dan master_judges
                        $randomNik = '1301' . $faker->unique()->numerify('############');

                        // Buat Dummy User untuk referensi user_id
                        $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                            'name'       => $hakim['nama'],
                            'username'   => $randomNik, // Masukkan random NIK ke kolom username
                            'email'      => 'hakim_' . uniqid() . '@mtq.test', 
                            'password'   => bcrypt('password123'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $masterJudgeId = \Illuminate\Support\Facades\DB::table('master_judges')->insertGetId([
                            'user_id'       => $userId,
                            'full_name'     => $hakim['nama'],
                            'nik'           => $randomNik, // Gunakan variabel yang sama dengan username
                            'date_of_birth' => $faker->dateTimeBetween('-60 years', '-30 years')->format('Y-m-d'),
                            'gender'        => $faker->randomElement(['MALE', 'FEMALE']),
                            'education'     => 'SMA',
                            'is_active'     => true,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                        $insertedJudges++;
                    } else {
                        $masterJudgeId = $existingMasterJudge->id;
                        $skippedJudges++;
                    }

                    // 3. Cek & Insert Event Judges (Berdasarkan event_id dan master_judge_id)
                    $existingEventJudge = \Illuminate\Support\Facades\DB::table('event_judges')
                        ->where('event_id', $eventId)
                        ->where('master_judge_id', $masterJudgeId)
                        ->first();

                    if (!$existingEventJudge) {
                        $judgeCode = 'MJH-' . str_pad($hakim['nomor'], 2, '0', STR_PAD_LEFT);
                        $eventJudgeId = \Illuminate\Support\Facades\DB::table('event_judges')->insertGetId([
                            'event_id'        => $eventId,
                            'master_judge_id' => $masterJudgeId,
                            'sequence'        => $hakim['nomor'],
                            'judge_code'      => $judgeCode,
                            'is_active'       => true,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    } else {
                        $eventJudgeId = $existingEventJudge->id;
                    }

                    // 4. Insert ke event_judge_panel_members (Karena sudah dihapus di awal, langsung insert tanpa cek)
                    $isChief = ($hakim['jabatan'] === 'Ketua Bidang'); // Set is_chief = true jika Ketua
                    \Illuminate\Support\Facades\DB::table('event_judge_panel_members')->insert([
                        'event_judge_panel_id' => $panelId,
                        'event_judge_id'       => $eventJudgeId,
                        'is_chief'             => $isChief,
                        'order_number'         => $order + 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Proses Seed Dewan Hakim selesai!',
                'summary' => [
                    'baru_ditambahkan' => $insertedJudges,
                    'sudah_ada_dilewati' => $skippedJudges
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan seed data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memasukkan/Memperbarui 12 User Panitera dengan atribut lengkap dan menghubungkannya ke Majelis (MJL-01 s/d MJL-12).
     */
    public function seedPaniteraPanel()
    {
        // 1. Ambil Event Aktif
        $event = \App\Models\Event::first(); 
        $eventId = $event ? $event->id : 1;

        // 2. Ambil Role Panitera
        $paniteraRole = \App\Models\Role::where('slug', 'panitera')->orWhere('name', 'Panitera')->first();

        // 3. Ambil acuan data wilayah dari panitera yang sudah ada (11 panitera sebelumnya) atau dari $event
        $existingPanitera = \App\Models\User::where('role_id', $paniteraRole?->id)->first();
        $provinceId = $existingPanitera?->province_id ?? $event?->province_id ?? null;
        $regencyId  = $existingPanitera?->regency_id ?? $event?->regency_id ?? null;
        $districtId = $existingPanitera?->district_id ?? $event?->district_id ?? null;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $userProcessed = 0;
            $panelAssigned = 0;

            for ($i = 1; $i <= 12; $i++) {
                $suffix = str_pad($i, 2, '0', STR_PAD_LEFT);
                $uname  = "panitera_{$suffix}";
                $email  = "panitera{$suffix}@mtq.test";

                // 4. Buat atau Update User dengan menyertakan seluruh struktur kolom yang dibutuhkan
                $user = \App\Models\User::updateOrCreate(
                    ['username' => $uname],
                    [
                        'name'        => "Panitera {$suffix}",
                        'email'       => $email,
                        'username'    => $uname,
                        'password'    => \Illuminate\Support\Facades\Hash::make($uname),
                        'avatar'      => null,
                        'role_id'     => $paniteraRole?->id,
                        'event_id'    => $eventId,
                        'province_id' => $provinceId,
                        'regency_id'  => $regencyId,
                        'district_id' => $districtId,
                        'village_id'  => null,
                    ]
                );
                $userProcessed++;

                // 5. Cari Majelis berdasarkan kode (MJL-01 s/d MJL-12)
                $panelCode = "MJL-{$suffix}";
                $panel = \App\Models\EventJudgePanel::where('event_id', $eventId)
                    ->where('code', $panelCode)
                    ->first();

                // 6. Hubungkan Panitera ke Majelis menggunakan relasi Eloquent clerkAssignment()
                if ($panel) {
                    $user->clerkAssignment()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'event_judge_panel_id' => $panel->id,
                            'is_active'            => true,
                            'notes'                => "Ditugaskan otomatis ke Majelis {$panelCode}",
                        ]
                    );
                    $panelAssigned++;
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil memproses 12 user panitera dan menghubungkannya dengan majelis.',
                'summary' => [
                    'panitera_diproses'          => $userProcessed,
                    'panitera_terhubung_majelis' => $panelAssigned
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sinkronisasi Komponen Penilaian (Event Field Components)
     * Memperbarui data yang sudah ada, menambah yang baru, dan HANYA menghapus data yang sudah usang/tidak relevan.
     */
    public function syncFieldComponents()
    {
        // 1. Dapatkan event yang aktif (Atau default 1)
        $activeEvent = \App\Models\Event::first(); 
        $eventId = $activeEvent ? $activeEvent->id : 1;

        // 2. Data Komponen Penilaian (Hasil Konversi JSON ke Array PHP)
        $komponenData = [
            [
                "cabang_golongan" => "Fahm Al Qur'an Beregu",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Nilai Akhir", "bobot_persen" => 100, "max_nilai" => 100]
                ]
            ],
            [
                "cabang_golongan" => "Hafalan Al Qur'an 1 Juz + Tilawah",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 2, "nama_komponen" => "Tahfizh", "bobot_persen" => 100, "max_nilai" => 50],
                    ["urutan" => 3, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Hafalan Al Qur'an 1 Juz Non Tilawah",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 2, "nama_komponen" => "Tahfizh", "bobot_persen" => 100, "max_nilai" => 50],
                    ["urutan" => 3, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Hafalan Al Qur'an 10 Juz",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 2, "nama_komponen" => "Tahfizh", "bobot_persen" => 100, "max_nilai" => 50],
                    ["urutan" => 3, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Hafalan Al Qur'an 5 Juz + Tilawah",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 2, "nama_komponen" => "Tahfizh", "bobot_persen" => 100, "max_nilai" => 50],
                    ["urutan" => 3, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Hafalan Al Qur'an 5 Juz Non Tilawah",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 2, "nama_komponen" => "Tahfizh", "bobot_persen" => 100, "max_nilai" => 50],
                    ["urutan" => 3, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Karya Tulis Ilmiah Al Qur'an (KTIQ) Umum",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Bobot Materi", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 2, "nama_komponen" => "Kaedah dan Gaya Bahasa", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 3, "nama_komponen" => "Logika dan Organisasi Pesan", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 4, "nama_komponen" => "Prestasi", "bobot_persen" => 100, "max_nilai" => 20]
                ]
            ],
            [
                "cabang_golongan" => "Karya Tulis Ilmiah Hadits (KTIH) Umum",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Bobot Materi", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 2, "nama_komponen" => "Kaedah dan Gaya Bahasa", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 3, "nama_komponen" => "Logika dan Organisasi Pesan", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 4, "nama_komponen" => "Prestasi", "bobot_persen" => 100, "max_nilai" => 20]
                ]
            ],
            [
                "cabang_golongan" => "Khutbah Jum'at & Adzan Khatib + Mu'adzin",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Materi / Isi", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Bahasa / Retorika", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Adzan", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Kitab Standar Umum",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Qiraat", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Terjemahan dan Maksud", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Fashahah dan Adab", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Seni Baca Al Qur'an (Tilawah) Anak-anak",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Suara", "bobot_persen" => 100, "max_nilai" => 15],
                    ["urutan" => 4, "nama_komponen" => "Irama", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Seni Baca Al Qur'an (Tilawah) Cacat Netra",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Suara", "bobot_persen" => 100, "max_nilai" => 15],
                    ["urutan" => 4, "nama_komponen" => "Irama", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Seni Baca Al Qur'an (Tilawah) Dewasa",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Suara", "bobot_persen" => 100, "max_nilai" => 15],
                    ["urutan" => 4, "nama_komponen" => "Irama", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Seni Baca Al Qur'an (Tilawah) Remaja",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Suara", "bobot_persen" => 100, "max_nilai" => 15],
                    ["urutan" => 4, "nama_komponen" => "Irama", "bobot_persen" => 100, "max_nilai" => 25]
                ]
            ],
            [
                "cabang_golongan" => "Seni Baca Al Qur'an (Tilawah) Taman Kanak-Kanak",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Do'a & Terjemah", "bobot_persen" => 100, "max_nilai" => 40]
                ]
            ],
            [
                "cabang_golongan" => "Seni Kaligrafi Al Qur'an Dekorasi",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Kaidah", "bobot_persen" => 100, "max_nilai" => 35],
                    ["urutan" => 2, "nama_komponen" => "Keindahan", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 3, "nama_komponen" => "Keindahan Hiasan", "bobot_persen" => 100, "max_nilai" => 40]
                ]
            ],
            [
                "cabang_golongan" => "Seni Kaligrafi Al Qur'an Hiasan Mushaf",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Kaidah", "bobot_persen" => 100, "max_nilai" => 35],
                    ["urutan" => 2, "nama_komponen" => "Keindahan", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 3, "nama_komponen" => "Keindahan Hiasan", "bobot_persen" => 100, "max_nilai" => 40]
                ]
            ],
            [
                "cabang_golongan" => "Seni Kaligrafi Al Qur'an Kontemporer",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Kaidah", "bobot_persen" => 100, "max_nilai" => 35],
                    ["urutan" => 2, "nama_komponen" => "Keindahan", "bobot_persen" => 100, "max_nilai" => 25],
                    ["urutan" => 3, "nama_komponen" => "Keindahan Hiasan", "bobot_persen" => 100, "max_nilai" => 40]
                ]
            ],
            [
                "cabang_golongan" => "Seni Kaligrafi Al Qur'an Naskah",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Kaidah", "bobot_persen" => 100, "max_nilai" => 60],
                    ["urutan" => 2, "nama_komponen" => "Keindahan", "bobot_persen" => 100, "max_nilai" => 40]
                ]
            ],
            [
                "cabang_golongan" => "Syarhil Qur'an Beregu",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Terjemah dan Materi", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Penghayatan dan Retorika", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Tilawah dan Adab", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Tafsir Al Qur'an Bahasa Arab",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Mufrodat Ayat & Ta'bir", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Mufrodat & Munasabah Ayat", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Wawasan & Durus", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Tafsir Al Qur'an Bahasa Indonesia",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Mufrodat Ayat & Ta'bir", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Mufrodat & Munasabah Ayat", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Wawasan & Durus", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Tafsir Al Qur'an Bahasa Inggris",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Mufrodat Ayat & Ta'bir", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Mufrodat & Munasabah Ayat", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Wawasan & Durus", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Tartil Al Qur'an Umum",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Irama dan Suara", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Tartil Al Qur'an Dasar",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Irama dan Suara", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ],
            [
                "cabang_golongan" => "Tartil Al Qur'an Menengah",
                "komponen" => [
                    ["urutan" => 1, "nama_komponen" => "Tajwid", "bobot_persen" => 100, "max_nilai" => 40],
                    ["urutan" => 2, "nama_komponen" => "Fashahah", "bobot_persen" => 100, "max_nilai" => 30],
                    ["urutan" => 3, "nama_komponen" => "Irama dan Suara", "bobot_persen" => 100, "max_nilai" => 30]
                ]
            ]
        ];

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 3. Ambil seluruh event_group_id yang aktif pada event saat ini
            $eventGroupIds = \App\Models\EventGroup::where('event_id', $eventId)->pluck('id');
            
            $syncedGroups = 0;
            $syncedComponents = 0;
            $skippedGroups = [];
            
            // Array untuk melacak ID EventFieldComponent mana saja yang valid (terdapat di JSON ini)
            $validComponentIds = [];

            // 4. Looping Data Master dan Lakukan Sync (Tanpa Menghapus Awal)
            foreach ($komponenData as $data) {
                
                // Cari Cabang Golongan dari tabel EventGroup berdasarkan full_name
                $group = \App\Models\EventGroup::where('event_id', $eventId)
                    ->where('full_name', $data['cabang_golongan'])
                    ->first();

                // Jika nama grup tidak ditemukan, catat agar ketahuan dan abaikan (skip) 
                if (!$group) {
                    $skippedGroups[] = $data['cabang_golongan'];
                    continue;
                }

                $syncedGroups++;

                // Iterasi komponen di dalam cabang golongan tersebut
                foreach ($data['komponen'] as $komp) {
                    
                    // Cek apakah Master Nama Komponen ini sudah ada di tabel list_fields, jika tidak buat baru
                    $listField = \App\Models\ListField::firstOrCreate(
                        ['name' => $komp['nama_komponen']]
                    );

                    // Update atau Buat Komponen baru. Menggunakan kombinasi 'event_group_id' dan 'field_id'
                    // sebagai key pencarian (unik) agar tidak terjadi duplikasi dan ID lama tetap dipertahankan.
                    $eventFieldComponent = \App\Models\EventFieldComponent::updateOrCreate(
                        [
                            'event_group_id' => $group->id,
                            'field_id'       => $listField->id,
                        ],
                        [
                            'event_group_name' => $group->full_name,
                            'field_name'       => $listField->name,
                            'weight'           => $komp['bobot_persen'],
                            'max_score'        => $komp['max_nilai'],
                            'order_number'     => $komp['urutan'],
                        ]
                    );

                    // Simpan ID yang ter-update/ter-create sebagai ID yang sah/valid
                    $validComponentIds[] = $eventFieldComponent->id;
                    $syncedComponents++;
                }
            }

            // 5. Bersihkan data obsolete/usang
            // Hapus HANYA komponen untuk event ini yang ID-nya TIDAK ADA dalam daftar valid/tersinkron
            $deletedObsoleteCount = 0;
            if ($eventGroupIds->isNotEmpty()) {
                $deletedObsoleteCount = \App\Models\EventFieldComponent::whereIn('event_group_id', $eventGroupIds)
                    ->whereNotIn('id', $validComponentIds)
                    ->delete();
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil melakukan sinkronisasi Master Komponen Penilaian tanpa merusak relasi existing.',
                'summary' => [
                    'event_id' => $eventId,
                    'total_cabang_disinkron' => $syncedGroups,
                    'total_komponen_diperbarui' => $syncedComponents,
                    'total_komponen_usang_dihapus' => $deletedObsoleteCount,
                    'cabang_tidak_ditemukan' => $skippedGroups
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan sinkronisasi data: ' . $e->getMessage()
            ], 500);
        }
    }
    
}