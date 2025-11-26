<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Event;
use App\Models\EventCompetitionBranch;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ParticipantsFromExcelSeeder extends Seeder
{
    /**
     * ID event tujuan import.
     * Silakan sesuaikan (misal event_id = 1).
     */
    protected int $eventId = 1;

    /**
     * Path file Excel relatif ke folder database/.
     */
    protected string $excelPath = 'seeders/data/data_participants.xlsx';

    public function run(): void
    {
        $fullPath = database_path($this->excelPath);

        if (!file_exists($fullPath)) {
            $this->command?->error("File Excel tidak ditemukan: {$fullPath}");
            return;
        }

        $this->command?->info("Import peserta dari: {$fullPath}");

        // Load Excel
        $spreadsheet = IOFactory::load($fullPath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $this->command?->warn('Sheet kosong atau hanya berisi header.');
            return;
        }

        // Asumsikan baris pertama adalah header
        $headerRow = array_shift($rows);
        $headers   = $this->normalizeHeaders($headerRow);

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                $data = $this->mapRowToData($row, $headers, $rowIndex + 2); // +2 karena sudah shift header

                if (!$data) {
                    // baris dilewati (misal NIK kosong atau gagal mapping)
                    continue;
                }

                // Insert / update berdasarkan kombinasi event_id + nik
                Participant::updateOrCreate(
                    [
                        'event_id' => $this->eventId,
                        'nik'      => $data['nik'],
                    ],
                    $data
                );
            }

            DB::commit();
            $this->command?->info('Import peserta selesai.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command?->error('Terjadi error saat import: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Normalisasi header Excel jadi array associative:
     *  - lower case
     *  - ganti spasi jadi underscore
     */
    protected function normalizeHeaders(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $col => $value) {
            $h = trim((string) $value);
            $h = strtolower($h);
            $h = str_replace([' ', '-', '.', '/'], '_', $h);
            $headers[$col] = $h;
        }
        return $headers;
    }

    /**
     * Mapping satu baris Excel ke data peserta.
     *
     * Silakan SESUAIKAN nama header Excel di sini.
     */
    protected function mapRowToData(array $row, array $headers, int $excelRowNumber): ?array
    {
        // Helper untuk ambil nilai kolom berdasarkan nama header
        $get = function (string $name) use ($row, $headers) {
            foreach ($headers as $col => $headerName) {
                if ($headerName === $name) {
                    return trim((string) ($row[$col] ?? ''));
                }
            }
            return '';
        };

        // === Ambil data mentah dari Excel (SESUAIKAN NAMA HEADER) ===
        $nik              = $get('nik');                // contoh header 'NIK'
        $fullName         = strtoupper($get('nama_lengkap'));       // contoh header 'Nama Lengkap'
        $phoneNumber      = $get('no_hp');              // contoh header 'No HP'
        $placeOfBirth     = $get('tempat_lahir');       // contoh header 'Tempat Lahir'
        $kecamatanExcel   = $get('kecamatan');          // contoh header 'Kecamatan'
        $kabupatenExcel   = $get('kabupaten_kota');     // kalau ada
        $provinceName     = $get('provinsi');           // kalau ada
        $villageName      = $get('kelurahan_desa');     // kalau ada
        $address          = $get('alamat');
        $education        = $get('pendidikan');         // misal 'SMA', 'S1' dll
        $branchCodeExcel  = $get('kode_cabang');        // contoh: kode cabang/golongan
        $branchName       = $get('cabang');        // contoh: kode cabang/golongan
        $bankAccount      = $get('no_rekening');
        $bankAccountName  = $get('nama_rekening');
        $bankName         = $get('nama_bank');

        // Jika NIK kosong → skip baris
        if (empty($nik)) {
            return null;
        }

        // === Tanggal lahir & gender dari NIK ===
        $dobGender = $this->extractBirthdateFromNik($nik);
        if (!$dobGender) {
            $this->command?->warn("Baris {$excelRowNumber}: NIK {$nik} tidak valid untuk extract tanggal lahir, baris dilewati.");
            return null;
        }

        // === Mapping wilayah ===
        // 1. Province
        $province   = null;
        $regency    = null;
        $district   = null;
        $village    = null;

        if ($provinceName !== '') {
            $province = Province::whereRaw('LOWER(name) = ?', [strtolower($provinceName)])->first();
        }

        // 2. REGENT DARI EXCEL (potong 3 karakter di depan → baru cocokan ke nama regency)
        //    Misal di Excel: "I. PESISIR SELATAN" → substr(3) = "PESISIR SELATAN"
        if ($kabupatenExcel !== '') {
            $cleanRegencyName = strlen($kabupatenExcel) > 3
                ? trim(substr($kabupatenExcel, 3))
                : trim($kabupatenExcel);

            $regencyQuery = Regency::query();
            if ($province) {
                $regencyQuery->where('province_id', $province->id);
            }

            $regency = $regencyQuery
                ->whereRaw('LOWER(name) = ?', [strtolower($cleanRegencyName)])
                ->first();
        }

        // 3. DISTRICT dari KECAMATAN (jika ada kolomnya)
        if ($kecamatanExcel !== '') {
            // Kalau di Excel juga punya numbering (contoh "01 - KECAMATAN X"), kamu bisa potong 3 karakter juga:
            $cleanDistrictName = strlen($kecamatanExcel) > 3
                ? trim(substr($kecamatanExcel, 3))
                : trim($kecamatanExcel);

            $districtQuery = District::query();
            if ($regency) {
                $districtQuery->where('regency_id', $regency->id);
            }

            $district = $districtQuery
                ->whereRaw('LOWER(name) = ?', [strtolower($cleanDistrictName)])
                ->first();
        }

        // 4. Village (opsional)
        if ($villageName !== '') {
            $villageQuery = Village::query();
            if ($district) {
                $villageQuery->where('district_id', $district->id);
            }

            $village = $villageQuery
                ->whereRaw('LOWER(name) = ?', [strtolower($villageName)])
                ->first();
        }

        if (!$province || !$regency || !$district) {
            $this->command?->warn("Baris {$excelRowNumber}: Gagal mapping wilayah untuk NIK {$nik}, baris dilewati.");
            return null;
        }

        // === Mapping event_competition_branch_id dari kode cabang (kalau ada) ===
        $eventBranch = null;
        if ($branchName !== '') {
            $branchName = str_replace('— ', '', $branchName);
            $eventBranch = EventCompetitionBranch::where('event_id', $this->eventId)
                ->where('name', $branchName)
                ->first();
        }

        if (!$eventBranch) {
            $this->command?->warn("{$branchName}  Baris {$excelRowNumber}: Kode cabang '{$branchCodeExcel}' tidak ditemukan untuk event_id {$this->eventId}, baris dilewati.");
            return null;
        }

        // Normalisasi education ke salah satu enum ['SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3']
        $education = strtoupper($education ?: 'SMA');
        $allowedEdu = ['SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'];
        if (!in_array($education, $allowedEdu, true)) {
            $education = 'SMA';
        }

        return [
            'event_id'                   => $this->eventId,
            'event_competition_branch_id'=> $eventBranch->id,
            'nik'                        => $nik,
            'full_name'                  => $fullName ?: $nik,
            'phone_number'               => $phoneNumber ?: null,
            'place_of_birth'             => $placeOfBirth ?: '-',
            'date_of_birth'              => $dobGender['date'],
            'gender'                     => $dobGender['gender'], // 'MALE'|'FEMALE'
            'province_id'                => $province->id,
            'regency_id'                 => $regency->id,
            'district_id'                => $district->id,
            'village_id'                 => $village?->id,
            'address'                    => $address ?: '-',
            'education'                  => $education,
            'bank_account_number'        => $bankAccount ?: null,
            'bank_account_name'          => $bankAccountName ?: null,
            'bank_name'                  => $bankName ?: null,
            'photo_url'                  => null,
            'id_card_url'                => null,
            'family_card_url'            => null,
            'bank_book_url'              => null,
            'certificate_url'            => null,
            'other_url'                  => null,
            'tanggal_terbit_ktp'         => null,
            'tanggal_terbit_kk'          => null,
        ];
    }

    /**
     * Ambil tanggal lahir & gender dari NIK.
     * Return: ['date' => 'Y-m-d', 'gender' => 'MALE'|'FEMALE'] atau null kalau gagal.
     */
    protected function extractBirthdateFromNik(string $nik): ?array
    {
        $nik = preg_replace('/\D/', '', $nik ?? '');
        if (strlen($nik) < 16) {
            return null;
        }

        $ddStr = substr($nik, 6, 2);
        $mmStr = substr($nik, 8, 2);
        $yyStr = substr($nik, 10, 2);

        $day   = (int) $ddStr;
        $month = (int) $mmStr;
        $year2 = (int) $yyStr;

        if ($day === 0 || $month === 0) {
            return null;
        }

        $gender = 'MALE';
        if ($day > 40) {
            $day   -= 40;
            $gender = 'FEMALE';
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return null;
        }

        $currentYear2 = (int) date('y');
        $fullYear     = $year2 <= $currentYear2 ? 2000 + $year2 : 1900 + $year2;

        $date = sprintf('%04d-%02d-%02d', $fullYear, $month, $day);

        return [
            'date'   => $date,
            'gender' => $gender,
        ];
    }
}
