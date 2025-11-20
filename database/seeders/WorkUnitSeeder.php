<?php

namespace Database\Seeders;

use App\Models\WorkUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        WorkUnit::truncate();
        $csvFile = fopen(base_path('database/data/work_unit.csv'), 'r');
        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ',')) !== false) {
            if (! $firstline) {
                WorkUnit::create([
                    'unit_name' => $data['1'],
                    'unit_code' => $data['0'],
                    'parent_unit' => $data['2']
                ]);
            }
            $firstline = false;
        }
        fclose($csvFile);
    }
}
