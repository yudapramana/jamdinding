<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orgs = [
            [ #1
                'name' => 'Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],
            [ #2
                'name' => 'Sub Bagian Tata Usaha - Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],
            [ #3
                'name' => 'Seksi Pendidikan Madrasah - Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],
            [ #4
                'name' => 'Seksi Penyelenggaraan Haji dan Umrah - Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],
            [ #5
                'name' => 'Seksi Pendidikan Agama Islam	- Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],
            [ #6
                'name' => 'Seksi Bimbingan Masyarakat Islam - Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],
            [ #7
                'name' => 'Seksi Pendidikan Diniyah dan Pondok Pesantren - Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],
            [ #8
                'name' => 'Penyelenggara Zakat dan Wakaf - Kantor Kementerian Agama Kabupaten Pesisir Selatan',
                'email' => 'pessel@kemenag.go.id'
            ],

            // MAN
            [
                'name' => 'MAN 1 Pesisir Selatan',
                'email' => 'man1.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MAN 2 Pesisir Selatan',
                'email' => 'man2.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MAN 3 Pesisir Selatan',
                'email' => 'man3.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MAN 4 Pesisir Selatan',
                'email' => 'man4.pessel@kemenag.go.id'
            ],

            // MTsN
            [
                'name' => 'MTsN 1 Pesisir Selatan',
                'email' => 'mtsn1.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 2 Pesisir Selatan',
                'email' => 'mtsn2.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 3 Pesisir Selatan',
                'email' => 'mtsn3.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 4 Pesisir Selatan',
                'email' => 'mtsn4.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 5 Pesisir Selatan',
                'email' => 'mtsn5.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 6 Pesisir Selatan',
                'email' => 'mtsn6.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 7 Pesisir Selatan',
                'email' => 'mtsn7.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 8 Pesisir Selatan',
                'email' => 'mtsn8.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 9 Pesisir Selatan',
                'email' => 'mtsn9.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 10 Pesisir Selatan',
                'email' => 'mtsn10.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 11 Pesisir Selatan',
                'email' => 'mtsn11.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 12 Pesisir Selatan',
                'email' => 'mtsn12.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MTsN 13 Pesisir Selatan',
                'email' => 'mtsn13.pessel@kemenag.go.id'
            ],


            // MIN
            // MTsN
            [
                'name' => 'MIN 1 Pesisir Selatan',
                'email' => 'min1.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 2 Pesisir Selatan',
                'email' => 'min2.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 3 Pesisir Selatan',
                'email' => 'min3.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 4 Pesisir Selatan',
                'email' => 'min4.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 5 Pesisir Selatan',
                'email' => 'min5.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 6 Pesisir Selatan',
                'email' => 'min6.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 7 Pesisir Selatan',
                'email' => 'min7.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 8 Pesisir Selatan',
                'email' => 'min8.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 9 Pesisir Selatan',
                'email' => 'min9.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 10 Pesisir Selatan',
                'email' => 'min10.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 11 Pesisir Selatan',
                'email' => 'min11.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 12 Pesisir Selatan',
                'email' => 'min12.pessel@kemenag.go.id'
            ],
            [
                'name' => 'MIN 13 Pesisir Selatan',
                'email' => 'min13.pessel@kemenag.go.id'
            ],


            // KUA
            [
                'name' => 'KUA KOTO XI TARUSAN',
                'email' => 'kua.kotoxitarusan@kemenag.go.id'
            ],
            [
                'name' => 'KUA BAYANG',
                'email' => 'kua.bayang@kemenag.go.id'
            ],
            [
                'name' => 'KUA IV NAGARI BAYANG UTARA',
                'email' => 'kua.bayu@kemenag.go.id'
            ],
            [
                'name' => 'KUA IV JURAI',
                'email' => 'kua.ivjurai@kemenag.go.id'
            ],
            [
                'name' => 'KUA BATANG KAPAS',
                'email' => 'kua.batangkapas@kemenag.go.id'
            ],
            [
                'name' => 'KUA SUTERA',
                'email' => 'kua.sutera@kemenag.go.id'
            ],
            [
                'name' => 'KUA LENGAYANG',
                'email' => 'kua.lengayang@kemenag.go.id'
            ],
            [
                'name' => 'KUA RANAH PESISIR',
                'email' => 'kua.ranahpesisir@kemenag.go.id'
            ],
            [
                'name' => 'KUA LINGGO SARI BAGANTI',
                'email' => 'kua.linggo@kemenag.go.id'
            ],
            [
                'name' => 'KUA AIRPURA',
                'email' => 'kua.airpura@kemenag.go.id'
            ],
            [
                'name' => 'KUA PANCUNG SOAL',
                'email' => 'kua.pancungsoal@kemenag.go.id'
            ],
            [
                'name' => 'KUA BASA AMPEK BALAI TAPAN',
                'email' => 'kua.bab@kemenag.go.id'
            ],
            [
                'name' => 'KUA RANAH AMPEK HULU TAPAN',
                'email' => 'kua.rahul@kemenag.go.id'
            ],
            [
                'name' => 'KUA LUNANG',
                'email' => 'kua.lunang@kemenag.go.id'
            ],
            [
                'name' => 'KUA SILAUT',
                'email' => 'kua.silaut@kemenag.go.id'
            ],
        ];
        

        foreach ($orgs as $key => $item) {
            \App\Models\Organization::firstOrCreate(
                ['name' => $item['name']],
                $item
            );
        }

        $data = [
            [
                'name' => 'Pramana Yuda Sayeti, S.Kom',
                'username' => '199407292022031002',
                'email' => '199407292022031002@kemenag.go.id',
                'password' => Hash::make('superadmin'),
                'updated_at' => \Carbon\Carbon::now(),
                'role_id' => \App\Enums\RoleType::SUPERADMIN->value,
                'can_multiple_role' => true,
            ],
            [
                'name' => 'Admin Event',
                'username' => 'adminevent',
                'email' => 'adminevent@kemenag.go.id',
                'password' => Hash::make('adminevent'),
                'updated_at' => \Carbon\Carbon::now(),
                'role_id' => \App\Enums\RoleType::ADMIN_EVENT->value,
                'can_multiple_role' => true,
            ],
            
        ];


        // DB::table('users')->insert($data);

        foreach ($data as $key => $item) {
            \App\Models\User::firstOrCreate(
                ['username' => $item['username']],
                $item
            );
        }
    }
}
