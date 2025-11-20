<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndonesiaLocationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Importing Indonesia Province / Regency / District / Village...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Kosongkan tabel sebelum seed ulang
        DB::table('villages')->truncate();
        DB::table('districts')->truncate();
        DB::table('regencies')->truncate();
        DB::table('provinces')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Import semua tabel
        $this->importCsv('provinces');
        $this->importCsv('regencies');
        $this->importCsv('districts');
        $this->importCsv('villages');

        $this->command->info('✓ Indonesia Location seeding DONE.');
    }

    /**
     * Import CSV with semicolon (;) delimiter.
     */
    private function importCsv(string $table)
    {
        $path = database_path("seeders/data/{$table}.csv");

        if (!file_exists($path)) {
            $this->command->error("File not found: {$path}");
            return;
        }

        $this->command->info("Importing {$table}.csv ...");

        $handle = fopen($path, 'r');

        if (!$handle) {
            $this->command->error("Failed to open {$path}");
            return;
        }

        // Gunakan delimiter semicolon (;)
        $header = fgetcsv($handle, 0, ';');

        $batchData = [];
        $batchSize = 1000;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            // Abaikan baris rusak
            if (count($row) !== count($header)) {
                continue;
            }

            $batchData[] = array_combine($header, $row);

            // insert per batch
            if (count($batchData) >= $batchSize) {
                DB::table($table)->insert($batchData);
                $batchData = [];
            }
        }

        if (!empty($batchData)) {
            DB::table($table)->insert($batchData);
        }

        fclose($handle);
    }
}
