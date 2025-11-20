<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stage;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            [
                'order_number' => 1,
                'name'         => 'Persiapan',
                'days'         => 3,
                'description'  => 'Tahapan awal untuk persiapan sistem, dokumen, dan koordinasi panitia.',
            ],
            [
                'order_number' => 2,
                'name'         => 'Pendaftaran',
                'days'         => 7,
                'description'  => 'Proses peserta atau kafilah mengisi data dan mengunggah berkas.',
            ],
            [
                'order_number' => 3,
                'name'         => 'Verifikasi I',
                'days'         => 3,
                'description'  => 'Pemeriksaan awal terhadap kelengkapan berkas peserta.',
            ],
            [
                'order_number' => 4,
                'name'         => 'Masa Sanggah',
                'days'         => 2,
                'description'  => 'Peserta/kafilah diberi kesempatan mengajukan sanggahan terhadap hasil verifikasi.',
            ],
            [
                'order_number' => 5,
                'name'         => 'Perbaikan Berkas',
                'days'         => 3,
                'description'  => 'Peserta/kafilah memperbaiki dokumen yang kurang atau salah.',
            ],
            [
                'order_number' => 6,
                'name'         => 'Verifikasi II',
                'days'         => 2,
                'description'  => 'Pemeriksaan akhir sebelum penetapan peserta.',
            ],
            [
                'order_number' => 7,
                'name'         => 'Penetapan Peserta',
                'days'         => 1,
                'description'  => 'Penetapan final daftar peserta yang akan mengikuti MTQ.',
            ],
            [
                'order_number' => 8,
                'name'         => 'Pendaftaran Ulang',
                'days'         => 1,
                'description'  => 'Proses konfirmasi kehadiran dan kelengkapan akhir peserta saat H-1.',
            ],
            [
                'order_number' => 9,
                'name'         => 'Pelaksanaan',
                'days'         => 5,
                'description'  => 'Hari pelaksanaan kegiatan MTQ.',
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
