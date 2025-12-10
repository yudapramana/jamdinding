<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\MasterBranch;

class __MasterBranchSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua data branch
        $branches = Branch::orderBy('order_number')->get();

        if ($branches->isEmpty()) {
            $this->command->warn("⚠️  Tidak ada data branches ditemukan.");
            return;
        }

        foreach ($branches as $branch) {

            MasterBranch::updateOrCreate(
                ['branch_id' => $branch->id], // supaya idempotent
                [
                    'branch_name'  => $branch->name,
                    'full_name'    => $branch->name, // kolom full_name sama dgn nama cabang
                    'order_number' => $branch->order_number,
                    'is_active'    => $branch->is_active,
                ]
            );
        }

        $this->command->info("✔ MasterBranchSeeder selesai. Total: {$branches->count()} cabang.");
    }
}
