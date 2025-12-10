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

        // Pastikan file CSV ada
        if (!file_exists($filePath)) {
            $this->command->error("CSV file not found at: {$filePath}");
            return;
        }

        // Ambil data ID dari tabel lain untuk referensi cepat (Wajib)
        $groups = DB::table('master_competition_groups')->pluck('id', 'name')->toArray();
        $categories = DB::table('master_competition_categories')->pluck('id', 'name')->toArray();

        $dataToInsert = [];
        $order = 1;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            // Ambil header baris pertama untuk identifikasi kolom
            // Delimiter diatur ke ';'
            $headers = fgetcsv($handle, 1000, ';'); 
            
            // Tentukan index berdasarkan header
            $groupIndex = array_search('Cabang', $headers);
            $categoryIndex = array_search('Kategori', $headers);
            $typeIndex = array_search('Jenis', $headers);
            $branchNameIndex = array_search('Golongan', $headers);
            // Menambahkan index untuk kolom baru
            $maxAgeIndex = array_search('Max Age', $headers); 

            while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
                // Pastikan baris memiliki jumlah kolom yang diharapkan (Minimal 5)
                if (count($row) < 5) continue; 

                $groupName = $row[$groupIndex];
                $categoryName = $row[$categoryIndex];
                $type = $row[$typeIndex];
                $branchName = $row[$branchNameIndex];
                // Mengambil nilai max_age
                $maxAge = $row[$maxAgeIndex] ? (int) $row[$maxAgeIndex] : null; 

                $groupId = $groups[$groupName] ?? null;
                $categoryId = $categories[$categoryName] ?? null;

                // Validasi: Pastikan group dan category ada
                if (is_null($groupId) || is_null($categoryId)) {
                    $this->command->warn("Group or Category not found for: $groupName - $categoryName (Skipping)");
                    continue;
                }
                
                // Logika format: 'Beregu' -> grup, selain itu -> individu
                $format = (strtolower($categoryName) === 'beregu') ? 'grup' : 'individu';
                
                // Logika untuk membuat kode unik (Contoh: FAHM_AL_QURAN_BEREGU_PUTRA)
                $baseCode = strtoupper(str_replace([' ', '+', '-', '\''], '_', $branchName));
                $code = trim(preg_replace('/__+/', '_', $baseCode), '_');

                // Pastikan tipe adalah salah satu dari ENUM
                $validType = in_array($type, ['Putra', 'Putri']) ? $type : 'Putra';

                $dataToInsert[] = [
                    'code' => $code,
                    'master_competition_group_id' => $groupId,
                    'master_competition_category_id' => $categoryId,
                    'type' => $validType,
                    'format' => $format,
                    'name' => $branchName,
                    'max_age' => $maxAge, // Kolom baru
                    'order_number' => $order++,
                    'description' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            fclose($handle);
        }

        DB::table('master_competition_branches')->insert($dataToInsert);
        $this->command->info("Successfully seeded " . count($dataToInsert) . " competition branches.");
    }
}