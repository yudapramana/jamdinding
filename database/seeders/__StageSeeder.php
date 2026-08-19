<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stage;

class __StageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            [
                'order_number' => 1,
                'name'         => 'Persiapan',
                'days'         => 15, // 17 - 31 Agustus
                'description'  => 'Tahapan awal untuk persiapan sistem, dokumen, dan koordinasi panitia.',
                'is_active'    => true,
            ],
            [
                'order_number' => 2,
                'name'         => 'Pendaftaran',
                'days'         => 21, // 01 - 21 September
                'description'  => 'Proses peserta atau kafilah mengisi data dan mengunggah berkas.',
                'is_active'    => true,
            ],
            [
                'order_number' => 3,
                'name'         => 'Verifikasi I',
                'days'         => 9, // 28 - 31
                'description'  => 'Pemeriksaan awal terhadap kelengkapan berkas peserta.',
                'is_active'    => true,
            ],
            [
                'order_number' => 4,
                'name'         => 'Masa Sanggah',
                'days'         => 2, // 1 - 2
                'description'  => 'Peserta/kafilah diberi kesempatan mengajukan sanggahan terhadap hasil verifikasi.',
                'is_active'    => true,
            ],
            [
                'order_number' => 5,
                'name'         => 'Perbaikan Berkas',
                'days'         => 5, // 3
                'description'  => 'Peserta/kafilah memperbaiki dokumen yang kurang atau salah.',
                'is_active'    => true,
            ],
            [
                'order_number' => 6,
                'name'         => 'Verifikasi II',
                'days'         => 11, // 4 - 5
                'description'  => 'Pemeriksaan akhir sebelum penetapan peserta.',
                'is_active'    => true,
            ],
            [
                'order_number' => 7,
                'name'         => 'Penetapan Peserta',
                'days'         => 13, // 6 - 19
                'description'  => 'Penetapan final daftar peserta yang akan mengikuti MTQ.',
                'is_active'    => true,
            ],
            [
                'order_number' => 8,
                'name'         => 'Pendaftaran Ulang',
                'days'         => 4, // 20
                'description'  => 'Proses konfirmasi kehadiran dan kelengkapan akhir peserta saat H-1.',
                'is_active'    => true,
            ],
            [
                'order_number' => 9,
                'name'         => 'Pelaksanaan',
                'days'         => 6, // 21 - 25
                'description'  => 'Hari pelaksanaan kegiatan MTQ.',
                'is_active'    => true,
            ],
        ];

        foreach ($stages as $stage) {
            Stage::updateOrCreate(
                ['name' => $stage['name']],
                $stage
            );
        }
    }
}
