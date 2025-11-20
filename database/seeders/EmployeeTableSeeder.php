<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class EmployeeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Employee::truncate();
        $csvFile = fopen(base_path('database/data/employee.csv'), 'r');
        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ';')) !== false) {
            if (! $firstline) {
                $workunit = WorkUnit::where('unit_code', $data[2])->first();

                $nip = $data['0'];
                $dob = substr($nip, 0, 4) . '-' . substr($nip, 4, 2) . '-' . substr($nip, 6, 2) ;
                $gender = (substr($nip, 14, 1) == 1) ? 'M' : 'F';

                if($workunit) {
                    $employee = Employee::create([
                        'nip' => $nip,
                        'full_name' => $data['1'],
                        'id_work_unit' => $workunit->id,
                        'job_title' => $data['3'],
                        'gol_ruang' => $data['4'],
                        'employment_status' => $data['5'],
                        'tmt_pangkat' => $data['6'],
                        'tmt_jabatan' => $data['7'],
                        'tmt_pensiun' => $data['8'],
                        'date_of_birth' => $dob,
                        'gender' => $gender,
                        'email' => $nip . '@kemenag.go.id'
                    ]);
                }
                
            }
            $firstline = false;
        }
        fclose($csvFile);
    }
}
