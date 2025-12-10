<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCompetitionBranchSeeder extends Seeder
{
    /**
     * Path ke file CSV.
     * @var string
     */
    protected $csvFile = 'competition_branches.csv';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = database_path('seeders/data/' . $this->csvFile);

        // Opsional: bersihkan dulu tabel anak → baru tabel branch
        DB::table('master_branch_field_components')->delete();
        DB::table('master_competition_branches')->delete();

        // Pastikan file CSV ada
        if (!file_exists($filePath)) {
            $this->command->error("CSV file not found at: {$filePath}");
            return;
        }

        // Ambil data ID dari tabel lain untuk referensi cepat (Wajib)
        $groups     = DB::table('master_competition_groups')->pluck('id', 'name')->toArray();
        $categories = DB::table('master_competition_categories')->pluck('id', 'name')->toArray();

        $dataToInsert = [];
        $order        = 1;

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Ambil header baris pertama untuk identifikasi kolom
            // Delimiter diatur ke ';'
            $headers = fgetcsv($handle, 1000, ';');

            // Tentukan index berdasarkan header
            $groupIndex      = array_search('Cabang',   $headers);
            $categoryIndex   = array_search('Kategori', $headers);
            $typeIndex       = array_search('Jenis',    $headers);
            $branchNameIndex = array_search('Golongan', $headers);
            $maxAgeIndex     = array_search('Max Age',  $headers); // Kolom baru

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                // Minimal 5 kolom
                if (count($row) < 5) {
                    continue;
                }

                $groupName    = $row[$groupIndex];
                $categoryName = $row[$categoryIndex];
                $type         = $row[$typeIndex];
                $branchName   = $row[$branchNameIndex];
                $maxAge       = $row[$maxAgeIndex] !== '' ? (int) $row[$maxAgeIndex] : null;

                $groupId    = $groups[$groupName]     ?? null;
                $categoryId = $categories[$categoryName] ?? null;

                // Validasi: Pastikan group dan category ada
                if (is_null($groupId) || is_null($categoryId)) {
                    $this->command->warn("Group or Category not found for: {$groupName} - {$categoryName} (Skipping)");
                    continue;
                }

                // Logika format: 'Beregu' -> grup, selain itu -> individu
                $format = (strtolower($categoryName) === 'beregu') ? 'grup' : 'individu';

                // Logika untuk membuat kode unik dari nama golongan
                $baseCode = strtoupper(str_replace([' ', '+', '-', '\''], '_', $branchName));
                $code     = trim(preg_replace('/__+/', '_', $baseCode), '_');

                // Pastikan tipe adalah salah satu dari ENUM
                $validType = in_array($type, ['Putra', 'Putri']) ? $type : 'Putra';

                $dataToInsert[] = [
                    'code'                         => $code,
                    'master_competition_group_id'  => $groupId,
                    'master_competition_category_id' => $categoryId,
                    'type'                         => $validType,
                    'format'                       => $format,
                    'name'                         => $branchName,
                    'max_age'                      => $maxAge,
                    'order_number'                 => $order++,
                    'description'                  => null,
                    'is_active'                    => true,
                    'created_at'                   => now(),
                    'updated_at'                   => now(),
                ];
            }

            fclose($handle);
        }

        if (count($dataToInsert)) {
            DB::table('master_competition_branches')->insert($dataToInsert);
        }

        $this->command->info("Successfully seeded " . count($dataToInsert) . " competition branches.");

        // ➜ Lanjut seeder master_branch_field_components
        $this->seedBranchFieldComponents();
    }

    /**
     * Seed master_branch_field_components berdasarkan group (Tilawah, Tartil, Syarhil, dll).
     *
     * Catatan:
     * - field_name HARUS ada di tabel assessment_fields.field_name
     */
    protected function seedBranchFieldComponents(): void
    {
        // Ambil daftar assessment_fields (key = field_name)
        $assessmentFields = DB::table('assessment_fields')->pluck('id', 'field_name')->toArray();

        // Konfigurasi bidang penilaian per GROUP cabang lomba
        // Sesuaikan nama group dengan master_competition_groups.name
        $groupFieldConfigs = [

            // 1. Fahm Al Qur'an
            "Fahm Al Qur'an" => [
                [
                    'code'         => 'PEMAHAMAN_ISI_MAKNA',
                    'field_name'   => 'Pemahaman Isi dan Makna',
                    'description'  => 'Tingkat pemahaman peserta terhadap makna ayat, konteks, dan pesan Al Qur’an.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KETEPATAN_JAWAB',
                    'field_name'   => 'Ketepatan Jawaban',
                    'description'  => 'Kesesuaian jawaban dengan soal, ketepatan dalil dan rujukan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KECEPATAN_JAWAB',
                    'field_name'   => 'Kecepatan Menjawab',
                    'description'  => 'Kecepatan peserta dalam merespons soal tanpa mengurangi ketepatan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'ADAB_DISIPLIN',
                    'field_name'   => 'Adab & Disiplin',
                    'description'  => 'Sikap, kerapian, dan etika peserta selama perlombaan.',
                    'default_unit' => 'point',
                ],
            ],

            // 2. Hafalan Al Qur'an
            "Hafalan Al Qur'an" => [
                [
                    'code'         => 'KELANCARAN_HAFALAN',
                    'field_name'   => 'Kelancaran Hafalan',
                    'description'  => 'Mengukur kelancaran hafalan tanpa terputus atau ragu berlebihan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KETEPATAN_LAFAL',
                    'field_name'   => 'Ketepatan Lafal',
                    'description'  => 'Ketepatan pengucapan huruf, harakat, dan panjang pendek bacaan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KETEPATAN_TEMPAT_AYAT',
                    'field_name'   => 'Ketepatan Tempat Ayat',
                    'description'  => 'Kemampuan menyambung ayat, berpindah halaman, dan mengingat letak ayat.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'TAJWID',
                    'field_name'   => 'Tajwid',
                    'description'  => 'Penerapan hukum tajwid sesuai kaidah saat membaca/menghafal.',
                    'default_unit' => 'point',
                ],
            ],

            // 3. Karya Tulis Ilmiah Al Qur'an (KTIQ)
            "Karya Tulis Ilmiah Al Qur'an (KTIQ)" => [
                [
                    'code'         => 'ORISINALITAS_GAGASAN',
                    'field_name'   => 'Orisinalitas Gagasan',
                    'description'  => 'Keaslian ide dan kebaruan topik yang diangkat dari perspektif Al Qur’an.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'SISTEMATIKA_PENULISAN',
                    'field_name'   => 'Sistematika Penulisan',
                    'description'  => 'Kerapian struktur ilmiah: pendahuluan, kajian pustaka, metode, hasil, simpulan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KETEPATAN_METODOLOGI',
                    'field_name'   => 'Ketepatan Metodologi',
                    'description'  => 'Kesesuaian metode penelitian dengan topik dan tujuan kajian.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PENGUASAAN_MATERI_PRESENTASI',
                    'field_name'   => 'Penguasaan Materi Saat Presentasi',
                    'description'  => 'Kemampuan menjelaskan isi karya tulis dan menjawab pertanyaan dewan hakim.',
                    'default_unit' => 'point',
                ],
            ],

            // 4. Karya Tulis Ilmiah Hadits (KTIH)
            "Karya Tulis Ilmiah Hadits (KTIH)" => [
                [
                    'code'         => 'ORISINALITAS_KTI',
                    'field_name'   => 'Orisinalitas & Kebaruan',
                    'description'  => 'Keaslian ide dan kontribusi terhadap kajian hadits.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'SISTEMATIKA_KTI',
                    'field_name'   => 'Sistematika Penulisan',
                    'description'  => 'Struktur ilmiah penulisan sesuai kaidah penelitian.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PENGUATAN_DALIL',
                    'field_name'   => 'Penguatan Dalil',
                    'description'  => 'Kelengkapan takhrij hadits, sanad, dan analisis matan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PENGUASAAN_MATERI',
                    'field_name'   => 'Penguasaan Materi',
                    'description'  => 'Kemampuan menjelaskan isi karya dan menjawab pertanyaan.',
                    'default_unit' => 'point',
                ],
            ],

            // 5. Khutbah Jum'at & Adzan
            "Khutbah Jum'at & Adzan" => [
                [
                    'code'         => 'STRUKTUR_KHUTBAH',
                    'field_name'   => 'Struktur Khutbah',
                    'description'  => 'Kelengkapan rukun khutbah, susunan materi, dan alur pembahasan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KUALITAS_MATERI_KHUTBAH',
                    'field_name'   => 'Kualitas Materi Khutbah',
                    'description'  => 'Kedalaman isi, relevansi dengan tema, dan penggunaan dalil.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'RETORIKA_PENYAMPAIAN',
                    'field_name'   => 'Retorika & Penyampaian',
                    'description'  => 'Artikulasi, intonasi, kontak mata, dan penguasaan mimbar.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'MAKHRAJ_ADZAN',
                    'field_name'   => 'Makharijul Huruf Adzan',
                    'description'  => 'Ketepatan bacaan lafaz adzan, makhraj huruf, dan panjang pendek.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'SUARA_ADZAN',
                    'field_name'   => 'Lagu & Suara Adzan',
                    'description'  => 'Kualitas suara, kestabilan nada, dan kekhusyukan lantunan adzan.',
                    'default_unit' => 'point',
                ],
            ],

            // 6. Kitab Standar
            "Kitab Standar" => [
                [
                    'code'         => 'KETEPATAN_MEMBACA_KITAB',
                    'field_name'   => 'Ketepatan Membaca Teks',
                    'description'  => 'Kemampuan membaca teks Arab gundul dengan benar.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PEMAHAMAN_ISI_KITAB',
                    'field_name'   => 'Pemahaman Isi Kitab',
                    'description'  => 'Pemahaman terhadap makna dan maksud teks yang dibaca.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KEMAMPUAN_APLIKASI',
                    'field_name'   => 'Kemampuan Mengaplikasikan',
                    'description'  => 'Mampu menghubungkan isi kitab dengan konteks kekinian.',
                    'default_unit' => 'point',
                ],
            ],

            // 7. Musabaqah Hafalan Hadits Nabi
            "Musabaqah Hafalan Hadits Nabi" => [
                [
                    'code'         => 'KELANCARAN_HAFALAN_HADITS',
                    'field_name'   => 'Kelancaran Hafalan Hadits',
                    'description'  => 'Kemampuan melafalkan hadits tanpa terputus dan tanpa ragu berlebihan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KETEPATAN_LAFAL_HADITS',
                    'field_name'   => 'Ketepatan Lafal Hadits',
                    'description'  => 'Ketepatan lafaz hadits sesuai sumber rujukan yang sahih.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KETEPATAN_SANAD',
                    'field_name'   => 'Ketepatan Rangkaian Sanad',
                    'description'  => 'Kemampuan mengurutkan sanad dengan benar.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PEMAHAMAN_MATAN',
                    'field_name'   => 'Pemahaman Matan Hadits',
                    'description'  => 'Pemahaman terhadap makna dan pelajaran yang terkandung dalam matan hadits.',
                    'default_unit' => 'point',
                ],
            ],

            // 8. Qiraat Al Qur'an
            "Qiraat Al Qur'an" => [
                [
                    'code'         => 'KETEPATAN_QIRAAT',
                    'field_name'   => 'Ketepatan Qiraat',
                    'description'  => 'Kesesuaian bacaan dengan qiraat (riwayat, wajah, dan kaidahnya).',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'FASHAHAH',
                    'field_name'   => 'Fashahah',
                    'description'  => 'Kefasihan dalam pengucapan huruf, kata, dan struktur kalimat.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'SUARA',
                    'field_name'   => 'Suara',
                    'description'  => 'Kualitas suara: bening, kuat, stabil, dan merdu.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'LAGU',
                    'field_name'   => 'Lagu & Variasi',
                    'description'  => 'Kesesuaian maqamat dan variasi nada.',
                    'default_unit' => 'point',
                ],
            ],

            // 9. Seni Baca Al Qur'an (Tilawah)
            "Seni Baca Al Qur'an (Tilawah)" => [
                [
                    'code'         => 'TAJWID', // dipakai juga oleh cabang lain
                    'field_name'   => 'Tajwid',
                    'description'  => 'Ketepatan penerapan hukum-hukum tajwid.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'FASHAHAH',
                    'field_name'   => 'Fashahah',
                    'description'  => 'Kefasihan bacaan, kerapihan lafaz, dan struktur kalimat.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'SUARA',
                    'field_name'   => 'Suara',
                    'description'  => 'Kualitas suara: bening, kuat, stabil, dan merdu.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'LAGU',
                    'field_name'   => 'Lagu & Variasi',
                    'description'  => 'Kesesuaian lagu, variasi maqamat, serta penguasaan nada.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'ADAB_PENAMPILAN',
                    'field_name'   => 'Adab & Penampilan',
                    'description'  => 'Etika di depan mimbar, berpakaian, dan sikap terhadap mushaf.',
                    'default_unit' => 'point',
                ],
            ],

            // 10. Seni Kaligrafi Al Qur'an
            "Seni Kaligrafi Al Qur'an" => [
                [
                    'code'         => 'KOMPOSISI',
                    'field_name'   => 'Keserasian Komposisi',
                    'description'  => 'Penataan huruf, ayat, dan ornamen sehingga membentuk komposisi yang harmonis.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'BENTUK_HURUF',
                    'field_name'   => 'Ketepatan Bentuk Huruf',
                    'description'  => 'Kesesuaian bentuk huruf dengan kaidah khat yang digunakan.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KERAPIHAN_GORESAN',
                    'field_name'   => 'Kerapihan & Kualitas Goresan',
                    'description'  => 'Kualitas goresan, ketebalan garis, dan kebersihan hasil karya.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PENGGUNAAN_WARNA',
                    'field_name'   => 'Penggunaan Warna',
                    'description'  => 'Kecocokan dan keindahan kombinasi warna dalam karya kaligrafi.',
                    'default_unit' => 'point',
                ],
            ],

            // 11. Syarhil Qur'an
            "Syarhil Qur'an" => [
                [
                    'code'         => 'TILAWAH_PEMBUKA',
                    'field_name'   => 'Tilawah Pembuka',
                    'description'  => 'Kualitas tilawah pembuka sebagai bagian dari rangkaian syarhil.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KUALITAS_MATERI_SYARAH',
                    'field_name'   => 'Kualitas Materi Syarah',
                    'description'  => 'Kedalaman isi, ketepatan pemahaman ayat, dan relevansi tema.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'RETORIKA_PENYAMPAIAN',
                    'field_name'   => 'Retorika & Penyampaian',
                    'description'  => 'Kejelasan suara, intonasi, ekspresi, dan kemampuan memengaruhi audiens.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KERJASAMA_TIM',
                    'field_name'   => 'Kerjasama Tim',
                    'description'  => 'Kekompakan tilawah, pencerita, dan puitisasi dalam satu tim.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'ESTETIKA_KREATIVITAS',
                    'field_name'   => 'Estetika & Kreativitas',
                    'description'  => 'Penggunaan gaya bahasa, puitisasi, dan visual pendukung (bila ada).',
                    'default_unit' => 'point',
                ],
            ],

            // 12. Tafsir Al Qur'an
            "Tafsir Al Qur'an" => [
                [
                    'code'         => 'PENGUASAAN_ILMU_TAFSIR',
                    'field_name'   => 'Penguasaan Ilmu Tafsir',
                    'description'  => 'Pemahaman terhadap kaidah tafsir, asbabun nuzul, dan pendapat ulama.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KETEPATAN_PENAFSIRAN',
                    'field_name'   => 'Ketepatan Penafsiran',
                    'description'  => 'Kesesuaian penafsiran dengan kaidah yang mu\'tabar dan literatur sahih.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PENGUATAN_DALIL_TAFSIR',
                    'field_name'   => 'Penguatan Dalil',
                    'description'  => 'Penggunaan ayat lain, hadits, dan pendapat ulama sebagai penguat.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'PENYAMPAIAN_TAFSIR',
                    'field_name'   => 'Kemampuan Penyampaian',
                    'description'  => 'Kejelasan penuturan, alur penjelasan, dan kemampuan menjawab pertanyaan.',
                    'default_unit' => 'point',
                ],
            ],

            // 13. Tartil Al Qur'an
            "Tartil Al Qur'an" => [
                [
                    'code'         => 'TAJWID', // reuse
                    'field_name'   => 'Tajwid',
                    'description'  => 'Ketepatan bacaan sesuai kaidah tajwid.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'FASHAHAH_TARTIL',
                    'field_name'   => 'Fashahah',
                    'description'  => 'Kefasihan dan kerapihan bacaan tanpa kesalahan makhraj dan harakat.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'MAKHRAJ',
                    'field_name'   => 'Makharijul Huruf',
                    'description'  => 'Ketepatan pengucapan huruf hijaiyah dan sifat-sifat huruf.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'ADAB_TILAWAH',
                    'field_name'   => 'Adab & Kekhusyukan',
                    'description'  => 'Sikap khusyuk, tenang, dan adab tilawah Al Qur’an.',
                    'default_unit' => 'point',
                ],
            ],

            // 14. Tartil Al Qur'an Eksekutif (Eselon II)
            "Tartil Al Qur'an Eksekutif (Eselon II)" => [
                [
                    'code'         => 'TAJWID_DASAR',
                    'field_name'   => 'Tajwid Dasar',
                    'description'  => 'Penerapan tajwid dasar yang baik dan benar.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KELANCARAN_BACA',
                    'field_name'   => 'Kelancaran Bacaan',
                    'description'  => 'Kelancaran membaca tanpa banyak terputus atau salah.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'KUALITAS_SUARA',
                    'field_name'   => 'Kualitas Suara',
                    'description'  => 'Kejelasan suara, kestabilan, dan kenyamanan didengar.',
                    'default_unit' => 'point',
                ],
                [
                    'code'         => 'ADAB_TILAWAH_EKSEKUTIF',
                    'field_name'   => 'Adab Tilawah',
                    'description'  => 'Sikap hormat, khusyuk, dan penampilan saat tilawah.',
                    'default_unit' => 'point',
                ],
            ],
        ];

        // Ambil semua cabang lomba + nama group-nya
        $branches = DB::table('master_competition_branches as b')
            ->join('master_competition_groups as g', 'b.master_competition_group_id', '=', 'g.id')
            ->select('b.id', 'b.name', 'b.code', 'g.name as group_name')
            ->orderBy('g.name')
            ->orderBy('b.order_number')
            ->get();

        $componentsToInsert = [];
        $total = 0;

        foreach ($branches as $branch) {
            $groupName = $branch->group_name;

            if (!isset($groupFieldConfigs[$groupName])) {
                // Tidak semua group harus punya template; silent skip
                continue;
            }

            foreach ($groupFieldConfigs[$groupName] as $cfg) {
                $fieldName = $cfg['field_name'];

                if (!isset($assessmentFields[$fieldName])) {
                    $this->command->warn("Assessment field '{$fieldName}' not found. Skip for branch: {$branch->name}");
                    continue;
                }

                $componentsToInsert[] = [
                    'master_competition_branch_id' => $branch->id,
                    'assessment_field_id'          => $assessmentFields[$fieldName],
                    'default_weight'               => $cfg['weight']    ?? null,
                    'default_max_score'            => $cfg['max_score'] ?? null,
                    'default_order'                => $cfg['order']     ?? null,
                    'is_default'                   => true,
                    'created_at'                   => now(),
                    'updated_at'                   => now(),
                ];
                $total++;
            }
        }

        if (!empty($componentsToInsert)) {
            DB::table('master_branch_field_components')->insert($componentsToInsert);
            $this->command->info("Seeded {$total} master_branch_field_components.");
        } else {
            $this->command->warn('No master_branch_field_components generated. Please check mapping / assessment_fields.');
        }
    }
}
