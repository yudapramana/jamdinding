<?php

namespace Database\Seeders;

use App\Models\ListField;
use App\Models\MasterFieldComponent;
use App\Models\MasterGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class __MasterFieldComponentsSeeder extends Seeder
{
    /**
     * Nama file CSV.
     * Lokasi: database/seeders/data/master_field_components.csv
     *
     * Format header:
     * id_cabang;golongan_lomba;field_penilaian;default_weight
     *
     * @var string
     */
    protected string $csvFile = 'master_field_components.csv';

    public function run(): void
    {
        $path = database_path('seeders/data/' . $this->csvFile);

        if (! file_exists($path)) {
            $this->command?->error("File CSV tidak ditemukan: {$path}");
            return;
        }

        // Opsional: kosongkan dulu tabel
        // DB::table('master_field_components')->truncate();

        // Untuk mengatur default_order per master_group
        $orderPerGroup = [];

        if (($handle = fopen($path, 'r')) === false) {
            $this->command?->error("Gagal membuka file CSV: {$path}");
            return;
        }

        // Baca header
        $header = fgetcsv($handle, 0, ';');
        if (! $header) {
            $this->command?->error("Header CSV tidak valid.");
            fclose($handle);
            return;
        }

        // Normalisasi header ke lowercase
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $rowNumber++;

            // Map kolom ke associative array
            $data = [];
            foreach ($header as $index => $key) {
                $data[$key] = $row[$index] ?? null;
            }

            $idCabang       = trim($data['id_cabang'] ?? '');
            $golonganLomba  = trim($data['golongan_lomba'] ?? '');
            $fieldNameCsv   = trim($data['field_penilaian'] ?? '');
            $defaultWeight  = (int) ($data['default_weight'] ?? 0);

            if ($golonganLomba === '' || $fieldNameCsv === '') {
                $this->command?->warn("Baris {$rowNumber}: data tidak lengkap, dilewati.");
                continue;
            }

            // 1) Cari master_group (cabang lomba)
            //    Utamakan id_cabang -> id master_groups.
            $masterGroupQuery = MasterGroup::query();

            if ($idCabang !== '') {
                $masterGroupQuery->where('id', $idCabang);
            }

            $masterGroup = $masterGroupQuery->first();

            // fallback: cari berdasarkan full_name = golongan_lomba
            if (! $masterGroup) {
                $masterGroup = MasterGroup::where('full_name', $golonganLomba)->first();
            }

            if (! $masterGroup) {
                $this->command?->warn("Baris {$rowNumber}: master_group tidak ditemukan untuk '{$golonganLomba}' (id_cabang={$idCabang}).");
                continue;
            }

            // 2) Cari / buat entry list_fields (field penilaian)
            $field = ListField::firstOrCreate(
                ['name' => $fieldNameCsv],
                [
                    'code'          => Str::slug($fieldNameCsv, '_'),
                    'description'   => null,
                    'order_number'  => null,
                ]
            );

            // 3) Hitung default_order per master_group
            if (! isset($orderPerGroup[$masterGroup->id])) {
                $orderPerGroup[$masterGroup->id] = 0;
            }
            $orderPerGroup[$masterGroup->id]++;

            $defaultOrder = $orderPerGroup[$masterGroup->id];

            // 4) Insert / update ke master_field_components
            MasterFieldComponent::updateOrCreate(
                [
                    'master_group_id' => $masterGroup->id,
                    'field_id'        => $field->id,
                ],
                [
                    'master_group_name' => $masterGroup->full_name ?? ($masterGroup->branch_name . ' - ' . $masterGroup->group_name),
                    'field_name'        => $field->name,
                    'default_weight'    => 100,
                    'default_max_score' => $defaultWeight,      
                    'default_order'     => $defaultOrder,
                    'is_default'        => true,
                ]
            );
        }

        fclose($handle);

        $this->command?->info('✔ Seeder master_field_components selesai dijalankan.');
    }
}
