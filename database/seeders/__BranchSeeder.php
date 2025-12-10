<?php 

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class __BranchSeeder extends Seeder
{
    public function run()
    {
        $competitionGroups = [
            'Fahm Al Qur\'an',
            'Hafalan Al Qur\'an',
            'Karya Tulis Ilmiah Al Qur\'an (KTIQ)',
            'Karya Tulis Ilmiah Hadits (KTIH)',
            'Khutbah Jum\'at & Adzan',
            'Kitab Standar',
            'Musabaqah Hafalan Hadits Nabi',
            'Qiraat Al Qur\'an',
            'Seni Baca Al Qur\'an (Tilawah)',
            'Seni Kaligrafi Al Qur\'an',
            'Syarhil Qur\'an',
            'Tafsir Al Qur\'an',
            'Tartil Al Qur\'an',
            'Tartil Al Qur\'an Eksekutif (Eselon II)',
        ];

        // Cabang yang merupakan beregu
        $teamBranches = [
            'fahm al qur\'an',
            'khutbah jum\'at & adzan',
            'syarhil qur\'an',
        ];

        $removeTerms = ["Al Qur'an"];

        $order = 1;

        foreach ($competitionGroups as $originalName) {

            /** Nama disimpan tanpa diubah */
            $nameToSave = $originalName;

            /* ============================
               GENERATE CODE
            ============================ */

            $cleanForCode = $originalName;

            // Hilangkan "Al Qur'an" hanya untuk keperluan kode
            foreach ($removeTerms as $term) {
                $cleanForCode = str_replace($term, '', $cleanForCode);
            }

            // Hilangkan isi kurung
            $cleanForCode = preg_replace('/\s*\(.*?\)\s*/', '', $cleanForCode);

            // Rapikan spasi
            $cleanForCode = trim(preg_replace('/\s+/', ' ', $cleanForCode));

            $words = explode(' ', $cleanForCode);

            if (count($words) >= 3) {
                $code = strtoupper(
                    substr($words[0], 0, 1) . substr(end($words), 0, 1)
                );
            } elseif (count($words) == 2) {
                $code = strtoupper(
                    substr($words[0], 0, 1) . substr($words[1], 0, 1)
                );
            } else {
                // 1 kata → ambil huruf pertama + tengah
                $w = $words[0];
                $mid = floor(strlen($w) / 2);
                $code = strtoupper(substr($w, 0, 1) . substr($w, $mid, 1));
            }

            // Nomor urut
            $code = $code . '.' . str_pad($order, 2, '0', STR_PAD_LEFT);

            /* ============================
               Tentukan is_team
            ============================ */

            $normalizedName = strtolower(trim($originalName));

            $isTeam = in_array($normalizedName, $teamBranches);

            /* ============================
               SIMPAN KE DATABASE
            ============================ */

            DB::table('branches')->insert([
                'code'         => $code,
                'name'         => $nameToSave,
                'order_number' => $order,
                'is_team'      => $isTeam,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $order++;
        }
    }
}
