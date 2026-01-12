<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\MasterJudge;
use App\Models\EventJudge;
use App\Enums\RoleType;
use App\Models\Role;

class _EventJudgeSeeder extends Seeder
{
    public function run(): void
    {
        $eventId = 1; // ⚠️ GANTI sesuai event aktif

        /*
        |==================================================
        | RANDOM OPTIONS (SESUI UI)
        |==================================================
        */
        $specializationOptions = [
            'Tilawah',
            'Tahfidz',
            'Tafsir',
            'Fahmil',
            'Syarhil',
            'Khat',
            'Qiraat',
            'Hadits',
        ];

        $certificationOptions = [
            'Nasional',
            'Provinsi',
            'Kabupaten',
            'Kecamatan',
            'Internal',
            'Non Sertifikasi',
        ];

        $educationOptions = [
            'SD',
            'SMP',
            'SMA',
            'D1',
            'D2',
            'D3',
            'D4',
            'S1',
            'S2',
            'S3',
        ];

        /*
        |==================================================
        | DUMMY JUDGES (±15 DATA)
        |==================================================
        */
        $judges = [
            [
                'name' => 'Ahmad Fauzan',
                'email' => 'fauzan.hakim@example.com',
                'username' => 'fauzan.hakim',
                'nik' => '1301021503900001',
                'date_of_birth' => '1990-03-15',
                'gender' => 'MALE',
                'bank_name' => 'BANK NAGARI',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Ahmad Fauzan',
                'judge_code' => 'JDG-01',
            ],
            [
                'name' => 'Muhammad Ridwan',
                'email' => 'ridwan.hakim@example.com',
                'username' => 'ridwan.hakim',
                'nik' => '1301021604880002',
                'date_of_birth' => '1988-04-16',
                'gender' => 'MALE',
                'bank_name' => 'BRI',
                'bank_account_number' => '2345678901',
                'bank_account_name' => 'Muhammad Ridwan',
                'judge_code' => 'JDG-02',
            ],
            [
                'name' => 'Nur Aisyah',
                'email' => 'aisyah.hakim@example.com',
                'username' => 'aisyah.hakim',
                'nik' => '1301022205920003',
                'date_of_birth' => '1992-05-22',
                'gender' => 'FEMALE',
                'bank_name' => 'BSI',
                'bank_account_number' => '3456789012',
                'bank_account_name' => 'Nur Aisyah',
                'judge_code' => 'JDG-03',
            ],
            [
                'name' => 'Syamsul Bahri',
                'email' => 'syamsul.hakim@example.com',
                'username' => 'syamsul.hakim',
                'nik' => '1301020101800004',
                'date_of_birth' => '1980-01-01',
                'gender' => 'MALE',
                'bank_name' => 'BNI',
                'bank_account_number' => '4567890123',
                'bank_account_name' => 'Syamsul Bahri',
                'judge_code' => 'JDG-04',
            ],
            [
                'name' => 'Hendra Saputra',
                'email' => 'hendra.hakim@example.com',
                'username' => 'hendra.hakim',
                'nik' => '1301021207820005',
                'date_of_birth' => '1982-07-12',
                'gender' => 'MALE',
                'bank_name' => 'MANDIRI',
                'bank_account_number' => '5678901234',
                'bank_account_name' => 'Hendra Saputra',
                'judge_code' => 'JDG-05',
            ],
            [
                'name' => 'Siti Rahmah',
                'email' => 'rahmah.hakim@example.com',
                'username' => 'rahmah.hakim',
                'nik' => '1301021006880006',
                'date_of_birth' => '1988-06-10',
                'gender' => 'FEMALE',
                'bank_name' => 'BRI',
                'bank_account_number' => '6789012345',
                'bank_account_name' => 'Siti Rahmah',
                'judge_code' => 'JDG-06',
            ],
            [
                'name' => 'Abdul Karim',
                'email' => 'karim.hakim@example.com',
                'username' => 'karim.hakim',
                'nik' => '1301020202750007',
                'date_of_birth' => '1975-02-02',
                'gender' => 'MALE',
                'bank_name' => 'BCA',
                'bank_account_number' => '7890123456',
                'bank_account_name' => 'Abdul Karim',
                'judge_code' => 'JDG-07',
            ],
            [
                'name' => 'Yuliana Putri',
                'email' => 'yuliana.hakim@example.com',
                'username' => 'yuliana.hakim',
                'nik' => '1301020509900008',
                'date_of_birth' => '1990-09-05',
                'gender' => 'FEMALE',
                'bank_name' => 'BSI',
                'bank_account_number' => '8901234567',
                'bank_account_name' => 'Yuliana Putri',
                'judge_code' => 'JDG-08',
            ],
            [
                'name' => 'Rahmat Hidayat',
                'email' => 'rahmat.hakim@example.com',
                'username' => 'rahmat.hakim',
                'nik' => '1301020709850009',
                'date_of_birth' => '1985-08-07',
                'gender' => 'MALE',
                'bank_name' => 'BNI',
                'bank_account_number' => '9012345678',
                'bank_account_name' => 'Rahmat Hidayat',
                'judge_code' => 'JDG-09',
            ],
            [
                'name' => 'Fitri Handayani',
                'email' => 'fitri.hakim@example.com',
                'username' => 'fitri.hakim',
                'nik' => '1301020303880010',
                'date_of_birth' => '1988-03-03',
                'gender' => 'FEMALE',
                'bank_name' => 'BRI',
                'bank_account_number' => '1122334455',
                'bank_account_name' => 'Fitri Handayani',
                'judge_code' => 'JDG-10',
            ],
            [
                'name' => 'Zulkifli',
                'email' => 'zulkifli.hakim@example.com',
                'username' => 'zulkifli.hakim',
                'nik' => '1301020404900011',
                'date_of_birth' => '1990-04-04',
                'gender' => 'MALE',
                'bank_name' => 'MANDIRI',
                'bank_account_number' => '2233445566',
                'bank_account_name' => 'Zulkifli',
                'judge_code' => 'JDG-11',
            ],
            [
                'name' => 'Lina Marlina',
                'email' => 'lina.hakim@example.com',
                'username' => 'lina.hakim',
                'nik' => '1301020609910012',
                'date_of_birth' => '1991-09-06',
                'gender' => 'FEMALE',
                'bank_name' => 'BCA',
                'bank_account_number' => '3344556677',
                'bank_account_name' => 'Lina Marlina',
                'judge_code' => 'JDG-12',
            ],
            [
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi.hakim@example.com',
                'username' => 'dedi.hakim',
                'nik' => '1301020808820013',
                'date_of_birth' => '1982-08-08',
                'gender' => 'MALE',
                'bank_name' => 'BNI',
                'bank_account_number' => '4455667788',
                'bank_account_name' => 'Dedi Kurniawan',
                'judge_code' => 'JDG-13',
            ],
            [
                'name' => 'Rina Oktavia',
                'email' => 'rina.hakim@example.com',
                'username' => 'rina.hakim',
                'nik' => '1301020907830014',
                'date_of_birth' => '1983-07-09',
                'gender' => 'FEMALE',
                'bank_name' => 'BSI',
                'bank_account_number' => '5566778899',
                'bank_account_name' => 'Rina Oktavia',
                'judge_code' => 'JDG-14',
            ],
            [
                'name' => 'Ilham Pratama',
                'email' => 'ilham.hakim@example.com',
                'username' => 'ilham.hakim',
                'nik' => '1301021111820015',
                'date_of_birth' => '1982-11-11',
                'gender' => 'MALE',
                'bank_name' => 'BANK NAGARI',
                'bank_account_number' => '6677889900',
                'bank_account_name' => 'Ilham Pratama',
                'judge_code' => 'JDG-15',
            ],
        ];

        /*
        |==================================================
        | ROLE
        |==================================================
        */
        $roleDewanHakim = Role::where('name', 'DEWAN_HAKIM')->firstOrFail();

        foreach ($judges as $data) {

            /*
            |==================================================
            | 1. USERS
            |==================================================
            */
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => strtoupper($data['name']),
                    'username'          => $data['username'],
                    'email_verified_at' => now(),
                    'password'          => Hash::make('password'),
                    'role_id'           => RoleType::DEWAN_HAKIM->value,
                    'remember_token'    => Str::random(10),
                    'is_active'         => true,
                    'event_id'          => $eventId,
                ]
            );

            $user->roles()->syncWithoutDetaching([$roleDewanHakim->id]);

            /*
            |==================================================
            | 2. MASTER JUDGES (RANDOMIZED)
            |==================================================
            */
            $masterJudge = MasterJudge::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => strtoupper($data['name']),
                    'nik' => $data['nik'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender'],

                    'specialization' => $specializationOptions[array_rand($specializationOptions)],
                    'certification_level' => $certificationOptions[array_rand($certificationOptions)],
                    'education' => $educationOptions[array_rand($educationOptions)],

                    'bank_name' => $data['bank_name'],
                    'bank_account_number' => $data['bank_account_number'],
                    'bank_account_name' => $data['bank_account_name'],

                    'is_active' => true,
                ]
            );

            /*
            |==================================================
            | 3. EVENT JUDGES
            |==================================================
            */
            EventJudge::updateOrCreate(
                [
                    'event_id' => $eventId,
                    'master_judge_id' => $masterJudge->id,
                ],
                [
                    'judge_code' => $data['judge_code'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ EventJudgeSeeder sukses (random specialization, certification & education)');
    }
}
