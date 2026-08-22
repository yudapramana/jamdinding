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
        | DEWAN HAKIM (12 DATA)
        | 'name' diisi sesuai Cabang Lomba pada
        | Berita Acara Penetapan Venue Lomba MTQN ke-XLII
        | Tingkat Kabupaten Pesisir Selatan Tahun 2026.
        | Kolom lain tetap DUMMY (bukan data asli hakim).
        |==================================================
        */
        $judges = [
            [
                'name' => 'Tilawah Dewasa dan Remaja',
                'email' => 'hakim01@example.com',
                'username' => 'hakim01',
                'nik' => '1301021503900001',
                'date_of_birth' => '1990-03-15',
                'gender' => 'MALE',
                'bank_name' => 'BANK NAGARI',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Hakim Cabang 01',
                'judge_code' => 'JDG-01',
            ],
            [
                'name' => 'Tilawah Anak-anak, TK dan Canet',
                'email' => 'hakim02@example.com',
                'username' => 'hakim02',
                'nik' => '1301021604880002',
                'date_of_birth' => '1988-04-16',
                'gender' => 'MALE',
                'bank_name' => 'BRI',
                'bank_account_number' => '2345678901',
                'bank_account_name' => 'Hakim Cabang 02',
                'judge_code' => 'JDG-02',
            ],
            [
                'name' => 'Hafiz 1 dan 5 Juz Non Tilawah serta 10 Juz',
                'email' => 'hakim03@example.com',
                'username' => 'hakim03',
                'nik' => '1301022205920003',
                'date_of_birth' => '1992-05-22',
                'gender' => 'FEMALE',
                'bank_name' => 'BSI',
                'bank_account_number' => '3456789012',
                'bank_account_name' => 'Hakim Cabang 03',
                'judge_code' => 'JDG-03',
            ],
            [
                'name' => 'Hafiz 1 dan 5 Juz Tilawah',
                'email' => 'hakim04@example.com',
                'username' => 'hakim04',
                'nik' => '1301020101800004',
                'date_of_birth' => '1980-01-01',
                'gender' => 'MALE',
                'bank_name' => 'BNI',
                'bank_account_number' => '4567890123',
                'bank_account_name' => 'Hakim Cabang 04',
                'judge_code' => 'JDG-04',
            ],
            [
                'name' => 'Kitab Standar',
                'email' => 'hakim05@example.com',
                'username' => 'hakim05',
                'nik' => '1301021207820005',
                'date_of_birth' => '1982-07-12',
                'gender' => 'MALE',
                'bank_name' => 'MANDIRI',
                'bank_account_number' => '5678901234',
                'bank_account_name' => 'Hakim Cabang 05',
                'judge_code' => 'JDG-05',
            ],
            [
                'name' => 'Tafsir',
                'email' => 'hakim06@example.com',
                'username' => 'hakim06',
                'nik' => '1301021006880006',
                'date_of_birth' => '1988-06-10',
                'gender' => 'FEMALE',
                'bank_name' => 'BRI',
                'bank_account_number' => '6789012345',
                'bank_account_name' => 'Hakim Cabang 06',
                'judge_code' => 'JDG-06',
            ],
            [
                'name' => 'Tartil Dasar Menengah dan Umum',
                'email' => 'hakim07@example.com',
                'username' => 'hakim07',
                'nik' => '1301020202750007',
                'date_of_birth' => '1975-02-02',
                'gender' => 'MALE',
                'bank_name' => 'BCA',
                'bank_account_number' => '7890123456',
                'bank_account_name' => 'Hakim Cabang 07',
                'judge_code' => 'JDG-07',
            ],
            [
                'name' => "Khutbah Jum'at dan Azan",
                'email' => 'hakim08@example.com',
                'username' => 'hakim08',
                'nik' => '1301020509900008',
                'date_of_birth' => '1990-09-05',
                'gender' => 'FEMALE',
                'bank_name' => 'BSI',
                'bank_account_number' => '8901234567',
                'bank_account_name' => 'Hakim Cabang 08',
                'judge_code' => 'JDG-08',
            ],
            [
                'name' => "Fahmil Qur'an",
                'email' => 'hakim09@example.com',
                'username' => 'hakim09',
                'nik' => '1301020709850009',
                'date_of_birth' => '1985-08-07',
                'gender' => 'MALE',
                'bank_name' => 'BNI',
                'bank_account_number' => '9012345678',
                'bank_account_name' => 'Hakim Cabang 09',
                'judge_code' => 'JDG-09',
            ],
            [
                'name' => "Syarhil Qur'an",
                'email' => 'hakim10@example.com',
                'username' => 'hakim10',
                'nik' => '1301020303880010',
                'date_of_birth' => '1988-03-03',
                'gender' => 'FEMALE',
                'bank_name' => 'BRI',
                'bank_account_number' => '1122334455',
                'bank_account_name' => 'Hakim Cabang 10',
                'judge_code' => 'JDG-10',
            ],
            [
                'name' => 'KTIQ dan KTIH',
                'email' => 'hakim11@example.com',
                'username' => 'hakim11',
                'nik' => '1301020404900011',
                'date_of_birth' => '1990-04-04',
                'gender' => 'MALE',
                'bank_name' => 'MANDIRI',
                'bank_account_number' => '2233445566',
                'bank_account_name' => 'Hakim Cabang 11',
                'judge_code' => 'JDG-11',
            ],
            [
                'name' => 'Kaligrafi',
                'email' => 'hakim12@example.com',
                'username' => 'hakim12',
                'nik' => '1301020609910012',
                'date_of_birth' => '1991-09-06',
                'gender' => 'FEMALE',
                'bank_name' => 'BCA',
                'bank_account_number' => '3344556677',
                'bank_account_name' => 'Hakim Cabang 12',
                'judge_code' => 'JDG-12',
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

        $this->command->info('✅ EventJudgeSeeder sukses (12 dewan hakim sesuai cabang lomba MTQN ke-XLII)');
    }
}