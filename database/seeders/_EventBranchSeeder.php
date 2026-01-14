<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventBranch;
use App\Models\MasterBranch;

class _EventBranchSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil event pertama
        $event = Event::first();

        if (! $event) {
            $this->command?->error("  Tidak ada data event. Seeder event_branches dibatalkan.");
            return;
        }

        // Ambil semua master_branches
        $masterBranches = MasterBranch::all();

        if ($masterBranches->isEmpty()) {
            $this->command?->error("  Tidak ada data master_branches. Seeder event_branches dibatalkan.");
            return;
        }

        /**
         * DAFTAR CABANG AKTIF
         */
        $activeBranches = [
            'Fahm Al Qur\'an',
            'Hafalan Al Qur\'an',
            'Karya Tulis Ilmiah Al Qur\'an (KTIQ)',
            'Karya Tulis Ilmiah Hadits (KTIH)',
            'Khutbah Jum\'at & Adzan',
            'Kitab Standar',
            'Seni Baca Al Qur\'an (Tilawah)',
            'Seni Kaligrafi Al Qur\'an',
            'Syarhil Qur\'an',
            'Tafsir Al Qur\'an',
            'Tartil Al Qur\'an',
        ];

        // Normalisasi (hindari typo spasi / case mismatch)
        $activeBranchesNormalized = collect($activeBranches)
            ->map(fn ($v) => trim(mb_strtolower($v)))
            ->toArray();

        $this->command?->info("  Mengisi event_branches untuk event: {$event->event_name}");

        foreach ($masterBranches as $index => $master) {

            $branchNameNormalized = trim(mb_strtolower($master->branch_name));

            $status = in_array(
                $branchNameNormalized,
                $activeBranchesNormalized,
                true
            ) ? 'active' : 'inactive';

            EventBranch::updateOrCreate(
                [
                    'event_id'  => $event->id,
                    'branch_id' => $master->branch_id,
                ],
                [
                    'branch_name'  => $master->branch_name,
                    'full_name'    => $master->full_name,
                    'status'       => $status,
                    'order_number' => $index + 1,
                ]
            );
        }

        $this->command?->info(
            "✔ Seeder event_branches selesai. Total: {$masterBranches->count()} cabang diproses."
        );
    }
}
