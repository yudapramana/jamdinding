<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\WorkUnit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ImportEmployeesPns extends Command
{
    protected $signature = 'employees:import-pns 
                            {path : Path ke file CSV}
                            {--delimiter= : Delimiter CSV (auto-detect jika kosong)}
                            {--chunk=1000 : Ukuran batch upsert}';

    protected $description = 'Import/Upsert PNS: update semua kolom employees, map work unit via kode_satuan_kerja, derive DOB/Gender dari NIP, dan buat user untuk entri baru.';

    public function handle()
    {
        $path = $this->argument('path');
        $delimiter = $this->option('delimiter');
        $chunkSize = (int) $this->option('chunk');

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return 1;
        }

        if (!$delimiter) {
            $firstLine = trim((string) file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)[0] ?? '');
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        }
        $this->info("Menggunakan delimiter: '{$delimiter}'");

        $fh = fopen($path, 'r');
        if (! $fh) {
            $this->error('Gagal membuka file.');
            return 1;
        }

        // Header
        $header = fgetcsv($fh, 0, $delimiter);
        if ($header === false || $header === null) {
            fclose($fh);
            $this->error('Gagal membaca header CSV.');
            return 1;
        }

        $norm = fn($s) => strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', (string)$s)));
        $header = array_map($norm, $header);

        // Wajib
        $required = [
            'nip','full_name','job_title','gol_ruang','employment_status',
            'kode_satuan_kerja','tmt_pangkat','tmt_jabatan','tmt_pensiun'
        ];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                fclose($fh);
                $this->error("Kolom '{$col}' wajib ada pada header CSV.");
                return 1;
            }
        }

        $buffer   = [];
        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            }
            $assoc = array_combine($header, $row) ?: [];

            // Trim string
            foreach ($assoc as $k => $v) {
                $assoc[$k] = is_string($v) ? trim($v) : $v;
            }

            // Ambil kolom dasar
            $nipRaw       = (string) Arr::get($assoc, 'nip', '');
            $nip          = preg_replace('/\D+/', '', $nipRaw); // digit only
            $fullName     = (string) Arr::get($assoc, 'full_name', '');
            $jobTitle     = (string) Arr::get($assoc, 'job_title', '');
            $golRuang     = strtoupper((string) Arr::get($assoc, 'gol_ruang', ''));
            $empStatusRaw = (string) Arr::get($assoc, 'employment_status', '');
            $empStatus    = $this->normalizeEmploymentStatus($empStatusRaw);
            $kodeSatker   = (string) Arr::get($assoc, 'kode_satuan_kerja', '');
            $emailCsv     = Arr::get($assoc, 'email'); // optional
            $phoneCsv     = Arr::get($assoc, 'phone_number') ?? Arr::get($assoc, 'no_hp') ?? Arr::get($assoc, 'hp') ?? Arr::get($assoc, 'telp');

            // Validasi minimal
            if ($nip === '' || $fullName === '' || $jobTitle === '' || $golRuang === '' || !$empStatus || $kodeSatker === '') {
                $skipped++;
                continue;
            }

            // WorkUnit dari kode_satuan_kerja SAJA
            $wu = WorkUnit::where('unit_code', $kodeSatker)->first();
            if (! $wu) { $skipped++; continue; }
            $workUnitId = $wu->id;

            // Tanggal: format khusus
            $tmtPangkatSrc = (string) Arr::get($assoc, 'tmt_pangkat', '');
            $tmtJabatanSrc = (string) Arr::get($assoc, 'tmt_jabatan', '');
            $tmtPensiunSrc = (string) Arr::get($assoc, 'tmt_pensiun', '');

            $tmtPangkat = $this->parseDateTmtPangkat($tmtPangkatSrc); // d-m-Y utama
            $tmtJabatan = $this->parseDateYmd($tmtJabatanSrc);        // Y-m-d utama
            $tmtPensiun = $this->parseDateYmd($tmtPensiunSrc);        // Y-m-d utama

            // DOB & Gender dari NIP
            [$dob, $gender] = $this->deriveDobAndGenderFromNip($nip);

            // Email (jika tidak ada di CSV, pakai nip@kemenag.go.id)
            $email = $emailCsv ? trim((string)$emailCsv) : ($nip ? "{$nip}@kemenag.go.id" : null);

            // Phone (opsional)
            $phone = $phoneCsv ? preg_replace('/[^0-9+]/', '', (string)$phoneCsv) : null;

            $buffer[] = [
                'nip'               => $nip,
                'full_name'         => $fullName,
                'date_of_birth'     => $dob,      // nullable
                'gender'            => $gender,   // nullable
                // 'phone_number'      => $phone,    // nullable
                'email'             => $email,    // nullable unique
                'job_title'         => $jobTitle,
                'gol_ruang'         => $golRuang,
                'id_work_unit'      => $workUnitId,
                'employment_status' => $empStatus,
                'tmt_pangkat'       => $tmtPangkat, // nullable jika gagal parse
                'tmt_jabatan'       => $tmtJabatan,
                'tmt_pensiun'       => $tmtPensiun,
                'updated_at'        => now(),
                'created_at'        => now(),
            ];

            if (count($buffer) >= $chunkSize) {
                $this->flush($buffer, $inserted, $updated);
                $buffer = [];
            }
        }
        fclose($fh);

        if (!empty($buffer)) {
            $this->flush($buffer, $inserted, $updated);
        }

        $this->info("Selesai. Inserted: {$inserted}, Updated: {$updated}, Skipped: {$skipped}");
        return 0;
    }

    /**
     * Upsert employees (update semua kolom), lalu buat user hanya untuk entri baru.
     */
    protected function flush(array $buffer, int &$inserted, int &$updated): void
    {
        DB::transaction(function () use ($buffer, &$inserted, &$updated) {

            $nips = array_column($buffer, 'nip');

            // Yang sudah ada SEBELUM upsert → updated
            $existingNips = Employee::whereIn('nip', $nips)->pluck('nip')->all();

            Employee::upsert(
                $buffer,
                ['nip'],
                [
                    'full_name',
                    'date_of_birth',
                    'gender',
                    // 'phone_number',
                    'email',
                    'job_title',
                    'gol_ruang',
                    'id_work_unit',
                    'employment_status',
                    'tmt_pangkat',
                    'tmt_jabatan',
                    'tmt_pensiun',
                    'updated_at',
                ]
            );

            $updated += count($existingNips);
            $newNips = array_values(array_diff($nips, $existingNips));
            $inserted += count($newNips);

            if (!empty($newNips)) {
                $newEmployees = Employee::whereIn('nip', $newNips)
                    ->get(['id','nip','full_name','email']);

                $now = now();
                $rows = [];
                foreach ($newEmployees as $emp) {
                    $username = $emp->nip;
                    if (User::where('username', $username)->exists()) continue;

                    $rows[] = [
                        'id_employee' => $emp->id,
                        'name'        => $emp->full_name,
                        'email'       => $emp->email, // nullable jika schema users mengizinkan
                        'username'    => $username,
                        'password'    => Hash::make($username), // hash NIP
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }

                if (!empty($rows)) {
                    DB::table('users')->insertOrIgnore($rows);
                }
            }
        });
    }

    /**
     * DOB (YYYY-MM-DD) & Gender (M/F) dari NIP:
     * - DOB: 8 char pertama: YYYYMMDD
     * - Gender: char ke-15 (1-indexed): '1'=M, '2'=F
     */
    protected function deriveDobAndGenderFromNip(string $nip): array
    {
        $dob = null; $gender = null;

        if (strlen($nip) >= 8) {
            $yyyy = substr($nip, 0, 4);
            $mm   = substr($nip, 4, 2);
            $dd   = substr($nip, 6, 2);
            if (ctype_digit($yyyy.$mm.$dd) && checkdate((int)$mm, (int)$dd, (int)$yyyy)) {
                $dob = "{$yyyy}-{$mm}-{$dd}";
            }
        }

        if (strlen($nip) >= 15) {
            $g = substr($nip, 14, 1);
            if ($g === '1') $gender = 'M';
            elseif ($g === '2') $gender = 'F';
        }

        return [$dob, $gender];
    }

    /**
     * Parse tmt_pangkat utama: d-m-Y (contoh: 01-04-2022), fallback ke Y-m-d.
     */
    protected function parseDateTmtPangkat(?string $src): ?string
    {
        $s = trim((string)$src);
        if ($s === '') return null;

        // Utama: d-m-Y
        $dt = Carbon::createFromFormat('d-m-Y', $s, 'UTC');
        if ($dt !== false) return $dt->format('Y-m-d');

        // Fallback: Y-m-d
        try {
            $dt2 = Carbon::parse($s);
            return $dt2->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse tanggal utama: Y-m-d (untuk tmt_jabatan & tmt_pensiun), fallback ke d-m-Y.
     */
    protected function parseDateYmd(?string $src): ?string
    {
        $s = trim((string)$src);
        if ($s === '') return null;

        // Utama: Y-m-d
        $dt = Carbon::createFromFormat('Y-m-d', $s, 'UTC');
        if ($dt !== false) return $dt->format('Y-m-d');

        // Fallback: d-m-Y
        $dt2 = Carbon::createFromFormat('d-m-Y', $s, 'UTC');
        if ($dt2 !== false) return $dt2->format('Y-m-d');

        // Last resort
        try {
            $parsed = Carbon::parse($s);
            return $parsed->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Normalisasi employment_status:
     * - CPNS -> PNS
     * - Izinkan hanya: PNS, PPPK
     * - Selain itu -> null (biar diskip aman)
     */
    protected function normalizeEmploymentStatus(?string $status): ?string
    {
        if ($status === null) return null;

        $v = strtoupper(trim($status));
        if ($v === 'CPNS') return 'PNS';
        if (in_array($v, ['PNS', 'PPPK'], true)) return $v;

        return null;
    }
}
