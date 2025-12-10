<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Group;
use App\Models\MasterGroup;

class __MasterGroupSeeder extends Seeder
{
    /**
     * Nama file CSV.
     * Lokasi: database/seeders/data/competition_branches.csv
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

        $this->command->info("📄 Membaca CSV: {$this->csvFile}");

        $handle = fopen($filePath, 'r');

        if (!$handle) {
            $this->command->error("❌ CSV gagal dibuka.");
            return;
        }

        $rowNumber = 0;
        $created = 0;

        while (($data = fgetcsv($handle, 1000, ';')) !== false) {

            $rowNumber++;

            // Skip header
            if ($rowNumber == 1) {
                continue;
            }

            // CSV columns:
            // 0: Branch
            // 1: Group
            // 2: Jenis (Putra / Putri)
            // 3: Golongan (Full Name)
            // 4: Max Age
            $branchName = trim($data[0]);
            $groupName  = trim($data[1]);
            $maxAge     = trim($data[4]);
            

            if (!$branchName || !$groupName) {
                $this->command->warn("⚠️ Row {$rowNumber} dilewati karena kosong.");
                continue;
            }

            // CARI branch_id
            $branch = Branch::where('name', $branchName)->first();

            if (!$branch) {
                $this->command->warn("⚠️ Branch tidak ditemukan: {$branchName}");
                continue;
            }

            // CARI group_id
            $group = Group::where('name', $groupName)->first();

            if (!$group) {
                $this->command->warn("⚠️ Group tidak ditemukan: {$groupName}");
                continue;
            }

            // FULL NAME: dari kolom ke-3 CSV
            $fullName = $branch->name . ' ' . $group->name;

            // Insert or update master_groups
            MasterGroup::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'group_id'  => $group->id,
                ],
                [
                    'branch_name' => $branch->name,
                    'group_name'  => $group->name,
                    'full_name'   => $fullName,
                    'max_age'     => $maxAge,

                    'is_team'     => $branch->is_team,       // mengikuti group
                    'order_number'=> $group->order_number, // mengikuti group
                    'is_active'   => true,
                ]
            );

            $created++;
        }

        fclose($handle);

        $this->command->info("✔ MasterGroupSeeder selesai. Total data dibuat/diupdate: {$created}");
    }
}
