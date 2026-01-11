<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Role;
use App\Models\EventJudge;
use App\Enums\RoleType;

class _BootstrapEventJudgesSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * =========================================
         * DATA DEWAN HAKIM MTQ
         * Kota Padang 2024
         * =========================================
         */
        $judgesBySpecialization = [

            'Tartil' => [
                'H. Wardas Tanjung',
                'H. Yusron Lubis',
                'Ermiwati Roza',
            ],

            'Tilawah Dewasa / Qiraat Saba' => [
                'H. M. Rafles',
                'Afdhil Fadhli',
                'Johni Efendi',
                'H. M. Nasir Sikumbang',
            ],

            'Tahfiz (1 Juz & 5 Juz)' => [
                'Bakri',
                'Parlaungan',
                'H. Irsyad',
                'H. Muchlis',
            ],

            "Fahmil Qur'an" => [
                'Hj. Elima Depi Desmal',
                'Muslim M',
                'H. Iklanedi',
            ],

            "Syarhil Qur'an" => [
                'Hj. Maiyunis',
                'Rita Gamasari',
                'Desri Nora',
            ],

            'Tilawah TK & Khutbah Jumat' => [
                'H. Djanuis Syukur',
                'H. Syafrijal Halim',
                'Aris Junaidi',
            ],
        ];

        // 🔗 EVENT TARGET
        $eventId = 1;

        // 🔗 ROLE
        $roleDewanHakim = Role::where('name', 'DEWAN_HAKIM')->firstOrFail();

        $counter = 1;

        foreach ($judgesBySpecialization as $specialization => $names) {
            foreach ($names as $name) {

                /**
                 * =================================
                 * GENERATE NIK & EMAIL (DUMMY)
                 * =================================
                 */
                $nik = '1371' . str_pad($counter, 10, '0', STR_PAD_LEFT);
                $email = $nik . '@emtq.com';

                /**
                 * =================================
                 * USER
                 * =================================
                 */
                $user = User::firstOrCreate(
                    ['username' => $nik],
                    [
                        'name'      => $name,
                        'email'     => $email,
                        'password'  => Hash::make('password'),
                        'role_id'   => RoleType::DEWAN_HAKIM->value,
                        'is_active' => true,
                    ]
                );

                // attach role (jika multi-role)
                $user->roles()->syncWithoutDetaching([
                    $roleDewanHakim->id,
                ]);

                /**
                 * =================================
                 * EVENT JUDGE
                 * =================================
                 */
                EventJudge::firstOrCreate(
                    [
                        'event_id' => $eventId,
                        'user_id'  => $user->id,
                    ],
                    [
                        'judge_code'          => 'HJ-' . str_pad($counter, 3, '0', STR_PAD_LEFT),
                        'specialization'      => $specialization,
                        'certification_level' => 'Kabupaten',
                        'is_active'           => true,
                    ]
                );

                $counter++;
            }
        }

        $this->command->info('✔ Event Judges MTQ Kota Padang 2024 berhasil dibuat.');
    }
}
