<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Category;
use App\Models\MasterCategory;

class __MasterCategorySeeder extends Seeder
{
    /**
     * Nama file CSV (lokasi: database/seeders/data/)
     * @var string
     */
    protected $csvFile = 'competition_branches.csv';

    public function run(): void
    {
        $filePath = database_path('seeders/data/' . $this->csvFile);

        if (!file_exists($filePath)) {
            $this->command->error("❌ CSV tidak ditemukan: {$filePath}");
            return;
        }

        $handle = fopen($filePath, 'r');

        if (!$handle) {
            $this->command->error("❌ CSV gagal dibuka: {$filePath}");
            return;
        }

        $rowNumber = 0;
        $created = 0;

        while (($data = fgetcsv($handle, 2000, ';')) !== false) {
            $rowNumber++;

            // Skip header (anggap header di baris pertama)
            if ($rowNumber === 1) {
                continue;
            }

            // Skip empty rows
            if (!is_array($data) || count($data) === 0) {
                continue;
            }

            // Pastikan minimal ada 3 kolom: branch, group, category
            // Kolom tambahan (golongan, max age) boleh ada atau tidak
            if (!isset($data[0]) || !isset($data[1]) || !isset($data[2])) {
                $this->command->warn("⚠️ Row {$rowNumber} dilewati: kolom kurang. Data: " . json_encode($data));
                continue;
            }

            $branchName   = trim($data[0]);
            $groupName    = trim($data[1]);
            $categoryName = trim($data[2]);

            if ($branchName === '' || $groupName === '' || $categoryName === '') {
                $this->command->warn("⚠️ Row {$rowNumber} dilewati: ada field kosong. Data: " . json_encode($data));
                continue;
            }

            // Cari referensi di tabel utama
            $branch = Branch::where('name', $branchName)->first();
            $group  = Group::where('name', $groupName)->first();
            $category = Category::where('name', $categoryName)->first();

            if (!$branch) {
                $this->command->warn("⚠️ Row {$rowNumber} dilewati: branch tidak ditemukan ('{$branchName}').");
                continue;
            }

            if (!$group) {
                $this->command->warn("⚠️ Row {$rowNumber} dilewati: group tidak ditemukan ('{$groupName}').");
                continue;
            }

            if (!$category) {
                $this->command->warn("⚠️ Row {$rowNumber} dilewati: category tidak ditemukan ('{$categoryName}').");
                continue;
            }

            // Full name: jika kolom ke-4 (golongan) tersedia gunakan itu, kalau tidak gabungkan
            $fullName = null;
            if (isset($data[3]) && trim($data[3]) !== '') {
                $fullName = trim($data[3]);
            } else {
                $fullName = "{$branch->name} - {$group->name} - {$category->name}";
            }

            // Masukkan / update ke master_categories
            MasterCategory::updateOrCreate(
                [
                    'branch_id'   => $branch->id,
                    'group_id'    => $group->id,
                    'category_id' => $category->id,
                ],
                [
                    'branch_name'   => $branch->name,
                    'group_name'    => $group->name,
                    'category_name' => $category->name,
                    'full_name'     => $fullName,
                    'order_number'  => null,
                    'is_active'     => true,
                ]
            );

            $created++;
        }

        fclose($handle);

        $this->command->info("✔ MasterCategoriesSeeder selesai. Baris diproses: {$rowNumber}, berhasil dibuat/diupdate: {$created}");
    }
}
