<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventCategory;
use App\Models\EventGroup;
use App\Models\EventBranch;
use App\Models\EventTeam;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class _EventParticipantsSeeder extends Seeder
{
    protected int $eventId = 1;
    protected ?Event $event = null;
    protected string $excelPath = 'seeders/data/data_participants.xlsx';

    public function run(): void
    {
        $fullPath = database_path($this->excelPath);

        if (!file_exists($fullPath)) {
            $this->command?->error("File Excel tidak ditemukan: {$fullPath}");
            return;
        }

        $this->event = Event::find($this->eventId);
        if (!$this->event) {
            $this->command?->error("Event ID {$this->eventId} tidak ditemukan.");
            return;
        }

        $this->command?->info("Import peserta: {$this->event->event_name}");

        $spreadsheet = IOFactory::load($fullPath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $this->command?->warn('Sheet kosong / hanya header.');
            return;
        }

        $headers = $this->normalizeHeaders(array_shift($rows));

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                $excelRow = $rowIndex + 2;

                $nik  = $this->getCell($row, $headers, 'nik');
                $nama = $this->getCell($row, $headers, 'nama_lengkap');

                try {
                    $mapped = $this->mapRowToData($row, $headers, $excelRow);

                    if (!$mapped) {
                        $this->command?->warn("SKIP | {$excelRow} | {$nik} | {$nama}");
                        continue;
                    }

                    [$participantData, $eventParticipantData] = $mapped;

                    /* =========================
                     * PARTICIPANT
                     * ========================= */
                    $participant = Participant::updateOrCreate(
                        ['nik' => $participantData['nik']],
                        $participantData
                    );

                    /* =========================
                     * EVENT TEAM (JIKA GRUP)
                     * ========================= */
                    $eventTeamId = null;

                    $eventGroup = EventGroup::find($eventParticipantData['event_group_id']);

                    if ($eventGroup && $eventGroup->is_team) {
                        $teamName = $eventParticipantData['contingent'];

                        if ($teamName) {
                            $eventTeam = EventTeam::firstOrCreate(
                                [
                                    'event_id'          => $this->eventId,
                                    'event_branch_id'   => $eventParticipantData['event_branch_id'],
                                    'event_group_id'    => $eventParticipantData['event_group_id'],
                                    'event_category_id' => $eventParticipantData['event_category_id'],
                                    'team_name'         => $teamName,
                                ],
                                [
                                    'contingent' => $teamName,
                                ]
                            );

                            $eventTeamId = $eventTeam->id;
                        }
                    }

                    /* =========================
                     * EVENT PARTICIPANT
                     * ========================= */
                    EventParticipant::updateOrCreate(
                        [
                            'event_id'       => $this->eventId,
                            'participant_id' => $participant->id,
                        ],
                        array_merge(
                            $eventParticipantData,
                            [
                                'participant_id' => $participant->id,
                                'event_team_id'  => $eventTeamId,
                            ]
                        )
                    );

                } catch (\Throwable $rowError) {
                    $this->command?->error(
                        "ERROR | {$excelRow} | {$nik} | {$nama} | {$rowError->getMessage()}"
                    );
                }
            }

            DB::commit();
            $this->command?->info('✔ Import peserta & tim selesai.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command?->error('FATAL | ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper kecil untuk ambil nilai kolom (khusus logging).
     */
    protected function getCell(array $row, array $headers, string $key): string
    {
        foreach ($headers as $col => $name) {
            if ($name === $key) {
                return trim((string) ($row[$col] ?? '-'));
            }
        }
        return '-';
    }

    /* =======================================================
     * ================= HELPER ASLI (UTUH) ===================
     * ======================================================= */

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
     * Return:
     * - null → baris dilewati
     * - [participantData, eventParticipantData]
     */
    protected function mapRowToData(array $row, array $headers, int $excelRowNumber): ?array
    {
        // Helper ambil kolom
        $get = function (string $name) use ($row, $headers) {
            foreach ($headers as $col => $headerName) {
                if ($headerName === $name) {
                    return trim((string) ($row[$col] ?? ''));
                }
            }
            return '';
        };

        // === SESUAIKAN nama header Excel di sini ===
        $nik              = $get('nik');
        $fullName         = strtoupper($get('nama_lengkap'));
        $phoneNumber      = $get('no_hp');
        $placeOfBirth     = $get('tempat_lahir');
        $kecamatanExcel   = $get('kecamatan');
        $kabupatenExcel   = $get('kabupaten_kota');
        $provinceName     = $get('provinsi');
        $villageName      = $get('kelurahan_desa');
        $address          = $get('alamat');
        $education        = $get('pendidikan');
        $bankAccount      = $get('no_rekening');
        $bankAccountName  = $get('nama_rekening');
        $bankNameRaw      = $get('nama_bank');
        $branchNameOld    = $get('cabang');          // lama, bisa berisi "Tilawah Dewasa Putra"
        $branchFullExcel  = $get('cabang_lomba');    // disarankan: full nama cabang MTQ
        $groupNameExcel   = $get('golongan_lomba');  // opsional
        $categoryNameExcel= $get('kategori');        // opsional
        $contingent       = $get('kontingen');       // opsional: nama kontingen/kab/kota

        if (empty($nik)) {
            return null;
        }

        // === Tanggal lahir & gender dari NIK ===
        $dobGender = $this->extractBirthdateFromNik($nik);
        if (!$dobGender) {
            $this->command?->warn("Baris {$excelRowNumber}: NIK {$nik} tidak valid untuk extract tanggal lahir, baris dilewati.");
            return null;
        }

        $dateOfBirth = $dobGender['date'];      // 'Y-m-d'
        $gender      = $dobGender['gender'];    // 'MALE'|'FEMALE'

        // === Hitung umur per age_limit_date / start_date event ===
        [$ageYear, $ageMonth, $ageDay] = $this->calculateAgeComponents($dateOfBirth);

        // === Mapping wilayah: province, regency (pakai potong 3), district, village ===
        $province = null;
        $regency  = null;
        $district = null;
        $village  = null;

        if ($provinceName !== '') {
            $province = Province::whereRaw('LOWER(name) = ?', [strtolower($provinceName)])->first();
        }

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

        if ($kecamatanExcel !== '') {
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

        // === Mapping cabang / golongan / kategori ke event_categories & event_groups ===
        // 1) Coba pakai full nama cabang lomba (disarankan): 'cabang_lomba'
        $fullCabangExcel = $branchFullExcel ?: $branchNameOld;

        $eventCategory = null;

        if ($fullCabangExcel !== '') {
            $fullCabangExcel = str_replace('— ', '', $fullCabangExcel);
            $eventCategory = EventCategory::where('event_id', $this->eventId)
                ->whereRaw('LOWER(full_name) = ?', [strtolower($fullCabangExcel)])
                ->first();
        }

        // 2) Kalau belum ketemu, coba kombinasi branch_name + group_name + category_name
        if (!$eventCategory && ($branchNameOld || $groupNameExcel || $categoryNameExcel)) {
            $query = EventCategory::where('event_id', $this->eventId);

            if ($branchNameOld !== '') {
                $query->whereRaw('LOWER(branch_name) = ?', [strtolower($branchNameOld)]);
            }
            if ($groupNameExcel !== '') {
                $query->whereRaw('LOWER(group_name) = ?', [strtolower($groupNameExcel)]);
            }
            if ($categoryNameExcel !== '') {
                $query->whereRaw('LOWER(category_name) = ?', [strtolower($categoryNameExcel)]);
            }

            $eventCategory = $query->first();
        }

        if (!$eventCategory) {
            $this->command?->warn(
                "Baris {$excelRowNumber}: Cabang lomba tidak ditemukan di event_categories ".
                "(cabang_lomba='{$fullCabangExcel}', cabang='{$branchNameOld}', golongan='{$groupNameExcel}', kategori='{$categoryNameExcel}'). Baris dilewati."
            );
            return null;
        }

        // 3) Cari event_group berdasarkan branch_id + group_id yang sama
        $eventGroup = EventGroup::where('event_id', $this->eventId)
            ->where('branch_id', $eventCategory->branch_id)
            ->where('group_id', $eventCategory->group_id)
            ->first();

        if (!$eventGroup) {
            $this->command?->warn(
                "Baris {$excelRowNumber}: EventGroup tidak ditemukan untuk ".
                "branch_id={$eventCategory->branch_id}, group_id={$eventCategory->group_id}. Baris dilewati."
            );
            return null;
        }

        // 4) Cari event_branch berdasarkan branch_id yang sama
        $eventBranch = EventBranch::where('event_id', $this->eventId)
            ->where('branch_id', $eventCategory->branch_id)
            ->first();

        if (!$eventBranch) {
            $this->command?->warn(
                "Baris {$excelRowNumber}: EventBranch tidak ditemukan untuk ".
                "branch_id={$eventCategory->branch_id}, group_id={$eventCategory->group_id}. Baris dilewati."
            );
            return null;
        }

        // Normalisasi education
        $education = strtoupper($education ?: 'SMA');
        $allowedEdu = ['SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'];
        if (!in_array($education, $allowedEdu, true)) {
            $education = 'SMA';
        }

        // Normalisasi / mapping nama bank ke enum
        $bankName = $this->normalizeBankName($bankNameRaw);

        $photoUrls = [
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767663960/Foto%20Siswa/339d74f1f07258cda423069e872f4722.jpg',
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767663928/Foto%20Siswa/4e8c980d2ddbf2f0f17eb740db85a17b.jpg',
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767663919/Foto%20Siswa/89f52c332192fac82fd0d912d3e204b2.jpg',
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767663892/Foto%20Siswa/images.jpg',
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767663880/Foto%20Siswa/DSC_0341.jpg',
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767663845/Foto%20Siswa/_FOTO_MAN_LAKI.jpg',
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767663844/Foto%20Siswa/_FOTO_MTS_LAKI.jpg',
            'https://res.cloudinary.com/dmynbnqtt/image/upload/v1767664312/Foto%20Siswa/mico-pasfoto-3x4.jpg',
        ];

        $photoUrl = $photoUrls[array_rand($photoUrls)];

        // ====== DATA UNTUK TABEL participants (bank data) ======
        $participantData = [
            'nik'                 => $nik,
            'full_name'           => $fullName ?: $nik,
            'phone_number'        => $phoneNumber ?: null,
            'place_of_birth'      => $placeOfBirth ?: '-',
            'date_of_birth'       => $dateOfBirth,
            'gender'              => $gender,
            'education'           => $education,

            'province_id'         => $province->id,
            'regency_id'          => $regency->id,
            'district_id'         => $district->id,
            'village_id'          => $village?->id,

            'province_name'       => ($province->name ?? null),
            'regency_name'        => ($regency->name ?? null),
            'district_name'       => ($district->name ?? null),
            'village_name'        => ($village->name ?? null),

            'address'             => $address ?: '-',

            'bank_account_number' => $bankAccount ?: null,
            'bank_account_name'   => $bankAccountName ?: null,
            'bank_name'           => $bankName,   // sudah dinormalisasi ke enum / null

            'photo_url'           => $photoUrl,
            'id_card_url'         => null,
            'family_card_url'     => null,
            'bank_book_url'       => null,
            'certificate_url'     => null,
            'other_url'           => null,

            'tanggal_terbit_ktp'  => null,
            'tanggal_terbit_kk'   => null,
        ];

        // ====== DATA UNTUK TABEL event_participants ======

        $contingent = match ($this->event->event_level) {
            'national' => $province?->name,
            'province' => $regency?->name,
            'regency'  => $district?->name,
            'district' => $village?->name,
            default    => null,
        };

        $eventParticipantData = [
            'event_id'         => $this->eventId,
            'event_branch_id'  => $eventBranch->id,
            'event_group_id'   => $eventGroup->id,
            'event_category_id'=> $eventCategory->id,

            'age_year'         => $ageYear ?? 0,
            'age_month'        => $ageMonth ?? 0,
            'age_day'          => $ageDay ?? 0,

            'contingent'       => $contingent ?: null,
            // registration_status, dll → pakai default dari migration
        ];

        return [$participantData, $eventParticipantData];
    }

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

        return [
            'date'   => sprintf('%04d-%02d-%02d', $fullYear, $month, $day),
            'gender' => $gender,
        ];
    }

    protected function calculateAgeComponents(string $dateOfBirth): array
    {
        if (!$this->event) {
            return [null, null, null];
        }

        $birth = Carbon::parse($dateOfBirth);
        $cutoffDate = $this->event->age_limit_date ?? $this->event->start_date ?? null;
        if (!$cutoffDate) {
            return [null, null, null];
        }

        $cutoff = Carbon::parse($cutoffDate);

        if ($birth->gt($cutoff)) {
            return [0, 0, 0];
        }

        $diff = $birth->diff($cutoff);

        return [$diff->y, $diff->m, $diff->d];
    }

    protected function normalizeBankName(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $rawUpper = strtoupper(trim($raw));

        $allowed = [
            // BANK BUMN
            'BRI','BNI','MANDIRI','BTN',
            // BANK SYARIAH
            'BSI','BRI SYARIAH','BNI SYARIAH','MANDIRI SYARIAH',
            // BANK SWASTA NASIONAL
            'BCA','CIMB NIAGA','PERMATA','PANIN','OCBC NISP',
            'DANAMON','MEGA','SINARMAS','BUKOPIN','MAYBANK','BTPN','J TRUST BANK',
            // BPD
            'BANK DKI','BANK BJB','BANK BJB SYARIAH','BANK JATENG','BANK JATIM',
            'BANK SUMUT','BANK NAGARI','BANK RIAU KEPRI','BANK SUMSEL BABEL',
            'BANK LAMPUNG','BANK KALSEL','BANK KALBAR','BANK KALTIMTARA',
            'BANK SULSEL BAR','BANK SULTRA','BANK SULUTGO','BANK NTB SYARIAH',
            'BANK NTT','BANK PAPUA','BANK MALUKU MALUT',
        ];

        $map = [
            'BANK BRI'                 => 'BRI',
            'BANK RAKYAT INDONESIA'    => 'BRI',
            'BANK BNI'                 => 'BNI',
            'BANK NEGARA INDONESIA'    => 'BNI',
            'BANK MANDIRI'             => 'MANDIRI',
            'BANK BTN'                 => 'BTN',
            'BANK BCA'                 => 'BCA',
            'BANK CIMB NIAGA'          => 'CIMB NIAGA',
            'BANK PERMATA'             => 'PERMATA',
            'BANK DANAMON'             => 'DANAMON',
            'BANK MEGA'                => 'MEGA',
            'BANK SINARMAS'            => 'SINARMAS',
            'BANK BUKOPIN'             => 'BUKOPIN',
            'BANK MAYBANK'             => 'MAYBANK',
            'BANK BTPN'                => 'BTPN',
            'BANK DKI'                 => 'BANK DKI',
            'BANK SUMUT'               => 'BANK SUMUT',
            'BANK NAGARI'              => 'BANK NAGARI',
        ];

        if (isset($map[$rawUpper])) {
            $rawUpper = $map[$rawUpper];
        }

        return in_array($rawUpper, $allowed, true) ? $rawUpper : null;
    }
}
