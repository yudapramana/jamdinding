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
                'days'         => 7, // 7 - 14 Agustus
                'description'  => 'Tahapan awal untuk persiapan sistem, dokumen, dan koordinasi panitia.',
                'is_active'    => true,
            ],
            [
                'order_number' => 2,
                'name'         => 'Pendaftaran',
                'days'         => 8, // 15 - 21 September
                'description'  => 'Proses peserta atau kafilah mengisi data dan mengunggah berkas.',
                'is_active'    => true,
            ],
            [
                'order_number' => 3,
                'name'         => 'Verifikasi I',
                'days'         => 17, // 22 September - 8 Oktober
                'description'  => 'Pemeriksaan awal terhadap kelengkapan berkas peserta.',
                'is_active'    => true,
            ],
            [
                'order_number' => 4,
                'name'         => 'Masa Sanggah',
                'days'         => 3, // 9 - 11 Oktober
                'description'  => 'Peserta/kafilah diberi kesempatan mengajukan sanggahan terhadap hasil verifikasi.',
                'is_active'    => true,
            ],
            [
                'order_number' => 5,
                'name'         => 'Perbaikan Berkas',
                'days'         => 2, // 12 - 13 Oktober
                'description'  => 'Peserta/kafilah memperbaiki dokumen yang kurang atau salah.',
                'is_active'    => true,
            ],
            [
                'order_number' => 6,
                'name'         => 'Verifikasi II',
                'days'         => 2, // 14 - 15 Oktober
                'description'  => 'Pemeriksaan akhir sebelum penetapan peserta.',
                'is_active'    => true,
            ],
            [
                'order_number' => 7,
                'name'         => 'Penetapan Peserta',
                'days'         => 5, // 16 - 20 Oktober
                'description'  => 'Penetapan final daftar peserta yang akan mengikuti MTQ.',
                'is_active'    => true,
            ],
            [
                'order_number' => 8,
                'name'         => 'Pendaftaran Ulang',
                'days'         => 7, // 21 - 27 Oktober
                'description'  => 'Proses konfirmasi kehadiran dan kelengkapan akhir peserta saat H-1.',
                'is_active'    => true,
            ],
            [
                'order_number' => 9,
                'name'         => 'Pelaksanaan',
                'days'         => 10, // 28 Oktober - 6 November
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
