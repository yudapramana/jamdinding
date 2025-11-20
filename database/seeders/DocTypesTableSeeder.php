<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocTypesTableSeeder extends Seeder
{
    public function run()
    {
        $docs = [
            ['Ijazah SD', 'IJZHSD', true],
            ['Ijazah SMP/Sederajat', 'IJZHSMP', true],
            ['Ijazah SMA/Sederajat', 'IJZHSMA', true],
            ['Ijazah D2/D3/S1/A-IV/S2', 'IJZHKLH', true, true],
            ['Transkrip Nilai D2/D3/S1/S2', 'TRANSNILAI', true, true],
            ['SK CPNS', 'SKCPNS', true],
            ['SK PNS', 'SKPNS', true],
            ['SK Kenaikan Pangkat', 'SKKP', true, true],
            ['SK Pengangkatan Pertama dalam Jabatan Fungsional', 'SKJFPERTAMA', false],
            ['SK Kenaikan Jenjang Jabatan Fungsional', 'SKNAIKJF', false, true],
            ['SK Pengangkatan Pertama dalam Jabatan Pelaksana', 'SKJPPERTAMA', false],
            ['SK Mutasi/Pindah dan SK Jabatan termasuk Perpindahan Jabatan Pelaksana', 'SKMUTJAB', true, true],
            ['SK Jabatan Pejabat Struktural Eselon III, IV, dan V', 'SKJABSTRUK', false, true],
            ['SK Jabatan Pejabat Fungsional bagi Pengawas, Kepala KUA, Kepala Madrasah dan JFT', 'SKJABFUNG', true, true],
            ['Surat Pernyataan Pelantikan', 'SPP', true, true],
            ['Berita Acara Sumpah Jabatan', 'BASUMPAH', true, true],
            ['Surat Pernyataan Melaksanakan Tugas', 'SPMT', true, true],
            ['Surat Penyataan Menduduki Jabatan', 'SPMJ', true, true],
            ['PAK Konvensional', 'PAKKONVENSIONAL', true, true],
            ['PAK Integrasi', 'PAKINTEGRASI', true, true],
            ['PAK Konversi 2 Tahun Terakhir', 'PAKKONVERSI', true, true],
            ['SKP 2 Tahun Terakhir', 'SKP', true, true],
            ['KGB 2 Periode Terakhir', 'KGB', true, true],
            ['KARPEG', 'KARPEG', true],
            ['NIP Konversi', 'NIPKONVERSI', true],
            ['NPWP', 'NPWP', true],
            ['Kartu Keluarga', 'KK', true],
            ['Buku Nikah', 'BUKUNKH', true],
            ['KTP Ybs', 'KTPYBS', true],
            ['KTP Pasangan', 'KTPPSGN', true],
            ['Akte Kelahiran Ybs', 'AKYBS', true],
            ['Akte Kelahiran Pasangan', 'AKPSGN', true],
            ['Akte Kelahiran Anak-Anak', 'AKANAK', true, true],
            ['KARIS/KARSU', 'KARISKARSU', true, true],
            ['Buku Rekening Gaji', 'BUKUREK', true],
        ];

        // Seed untuk PNS
        foreach ($docs as $doc) {
            DB::table('doc_types')->insert([
                'status'     => 'PNS',
                'type_name'  => $doc[0],
                'label'      => $doc[1],
                'mandatory'  => $doc[2] ?? true,
                'multiple'   => $doc[3] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        $docsPPPK = [
            ['Ijazah SD', 'IJZHSD', true],
            ['Ijazah SMP/Sederajat', 'IJZHSMP', true],
            ['Ijazah SMA/Sederajat', 'IJZHSMA', true],
            ['Ijazah D2/D3/S1/A-IV/S2', 'IJZHKLH', true, true],
            ['Transkrip Nilai D2/D3/S1/S2', 'TRANSNILAI', true, true],
            ['SK CPPPK', 'SKCPPPK', true],
            ['SK PPPK', 'SKPPPK', true],
            ['Persetujuan Teknis BKN', 'PERTEK', true],
            ['Perjanjian Kerja PPPK', 'PERKERJAPPPK', true],
            ['Surat Pernyataan Pelantikan', 'SPP', true],
            ['Surat Pernyataan Melaksanakan Tugas', 'SPMT', true],
            ['Surat Penyataan Menduduki Jabatan', 'SPMJ', true],
            ['SKP 2 Tahun Terakhir', 'SKP', true, true],
            ['KGB 2 Periode Terakhir', 'KGB', true, true],
            ['NPWP', 'NPWP', true],
            ['Kartu Keluarga', 'KK', true],
            ['Buku Nikah', 'BUKUNKH', true],
            ['KTP Ybs', 'KTPYBS', true],
            ['KTP Pasangan', 'KTPPSGN', true],
            ['Akte Kelahiran Ybs', 'AKYBS', true],
            ['Akte Kelahiran Pasangan', 'AKPSGN', true],
            ['Akte Kelahiran Anak-Anak', 'AKANAK', true, true],
            ['Buku Rekening Gaji', 'BUKUREK', true],
        ];


        // Seed untuk PPPK
        foreach ($docsPPPK as $doc) {
            DB::table('doc_types')->insert([
                'status'     => 'PPPK',
                'type_name'  => $doc[0],
                'label'      => $doc[1],
                'mandatory'  => $doc[2] ?? true,
                'multiple'   => $doc[3] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
