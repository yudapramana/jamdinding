<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventMaqra;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Import jika Anda membuat class Import terpisah, atau gunakan facade Excel
use Maatwebsite\Excel\Facades\Excel;
// Gunakan class ini jika ingin import secara manual tanpa membuat class Import terpisah
use PhpOffice\PhpSpreadsheet\IOFactory;

class EventMaqraController extends Controller
{

    /**
     * Mengimpor data maqra dari file Excel/CSV
     */
    public function import(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'file'           => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'], // Maks 2MB
        ]);

        $eventGroupId = $request->input('event_group_id');
        $file = $request->file('file');

        // Pastikan event group mendukung maqra drawing
        $eventGroup = EventGroup::findOrFail($eventGroupId);
        if (!$eventGroup->has_maqra_drawing) {
            return response()->json([
                'message' => 'Golongan ini tidak dikonfigurasi untuk menggunakan sistem cabut Maqra.',
            ], 422);
        }

        try {
            // 2. Baca isi file Excel/CSV menggunakan PhpSpreadsheet (tanpa perlu class Import terpisah)
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Asumsi: Baris pertama adalah Header, jadi kita lewati (index > 0)
            // Struktur Header: [Nomor Surat, Ayat Mulai, Ayat Akhir, Juz, Halaman]
            // Nama surat diambil otomatis dari config/quran.php berdasarkan nomor surat.
            // Status maqra otomatis tersedia (is_available = true).

            $importedCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header

                // Pastikan baris tidak kosong sepenuhnya
                if (empty(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== ''))) continue;

                // Ambil data dari kolom Excel
                // Kolom A (0): Nomor Surat
                // Kolom B (1): Ayat Mulai
                // Kolom C (2): Ayat Akhir
                // Kolom D (3): Juz (opsional)
                // Kolom E (4): Halaman (opsional)

                $surahNumber = isset($row[0]) && $row[0] !== '' ? (int) $row[0] : null;
                $startAyah   = isset($row[1]) && $row[1] !== '' ? (int) $row[1] : null;
                $endAyah     = isset($row[2]) && $row[2] !== '' ? (int) $row[2] : null;
                $juz         = isset($row[3]) && $row[3] !== '' ? (int) $row[3] : null;
                $page        = isset($row[4]) && $row[4] !== '' ? (int) $row[4] : null;

                // Validasi data per baris
                if (!$surahNumber || !$startAyah || !$endAyah) {
                    $errors[] = "Baris " . ($index + 1) . ": Data tidak lengkap (Nomor Surat, Ayat Mulai, dan Ayat Akhir wajib diisi).";
                    continue; // Skip baris ini, lanjut ke baris berikutnya
                }

                // Ambil nama surat dari config/quran.php
                $surah = config("quran.surahs.{$surahNumber}");
                if (!$surah) {
                    $errors[] = "Baris " . ($index + 1) . ": Nomor Surat {$surahNumber} tidak valid (harus 1 - 114).";
                    continue;
                }

                if ($endAyah < $startAyah) {
                    $errors[] = "Baris " . ($index + 1) . ": Ayat Akhir tidak boleh lebih kecil dari Ayat Mulai.";
                    continue;
                }

                // 3. Simpan ke Database
                EventMaqra::create([
                    'event_group_id' => $eventGroupId,
                    'surah_number'   => $surahNumber,
                    'surah_name'     => $surah['name'],
                    'start_ayah'     => $startAyah,
                    'end_ayah'       => $endAyah,
                    'juz'            => $juz,
                    'page'           => $page,
                    'is_available'   => true,
                ]);

                $importedCount++;
            }

            $message = "Berhasil mengimpor {$importedCount} data maqra.";
            if (count($errors) > 0) {
                $message .= " Namun, terdapat beberapa error.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'errors'  => $errors // Kirim daftar error ke frontend
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error import Maqra: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengimpor file. Pastikan format file sesuai. Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan daftar maqra berdasarkan filter event_group_id
     */
    public function index(Request $request)
    {
        // event_group_id wajib ada karena maqra spesifik per golongan lomba
        $eventGroupId = $request->get('event_group_id');
        if (!$eventGroupId) {
            return response()->json([
                'message' => 'event_group_id is required.',
            ], 422);
        }

        $search = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = EventMaqra::query()
            ->where('event_group_id', $eventGroupId);

        // Filter pencarian berdasarkan nama surat atau nomor surat
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('surah_name', 'like', "%{$search}%")
                  ->orWhere('surah_number', 'like', "%{$search}%");
            });
        }

        // Urutkan default berdasarkan nomor surat dan ayat
        $query->orderBy('surah_number')
              ->orderBy('start_ayah');

        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Menyimpan maqra baru ke bank data event
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'surah_number'   => ['required', 'integer', 'min:1', 'max:114'],
            'surah_name'     => ['required', 'string', 'max:150'],
            'start_ayah'     => ['required', 'integer', 'min:1'],
            'end_ayah'       => ['required', 'integer', 'gte:start_ayah'], // Harus >= start_ayah
            'juz'            => ['nullable', 'integer', 'min:1', 'max:30'],
            'page'           => ['nullable', 'integer', 'min:1', 'max:604'],
            'is_available'   => ['required', 'boolean'],
        ]);

        // Opsional: Validasi untuk memastikan golongan tersebut memang mendukung cabut maqra
        $eventGroup = EventGroup::findOrFail($validatedData['event_group_id']);
        if (!$eventGroup->has_maqra_drawing) {
            return response()->json([
                'message' => 'Golongan ini tidak dikonfigurasi untuk menggunakan sistem cabut Maqra.',
            ], 422);
        }

        $maqra = EventMaqra::create($validatedData);

        return response()->json([
            'message' => 'Data maqra berhasil ditambahkan.',
            'data'    => $maqra,
        ], 201);
    }

    /**
     * Memperbarui data maqra yang sudah ada
     */
    public function update(Request $request, EventMaqra $eventMaqra)
    {
        $validatedData = $request->validate([
            'event_group_id' => ['sometimes', 'required', 'exists:event_groups,id'],
            'surah_number'   => ['required', 'integer', 'min:1', 'max:114'],
            'surah_name'     => ['required', 'string', 'max:150'],
            'start_ayah'     => ['required', 'integer', 'min:1'],
            'end_ayah'       => ['required', 'integer', 'gte:start_ayah'],
            'juz'            => ['nullable', 'integer', 'min:1', 'max:30'],
            'page'           => ['nullable', 'integer', 'min:1', 'max:604'],
            'is_available'   => ['required', 'boolean'],
        ]);

        $eventMaqra->update($validatedData);

        return response()->json([
            'message' => 'Data maqra berhasil diperbarui.',
            'data'    => $eventMaqra,
        ]);
    }

    /**
     * Menghapus data maqra
     */
    public function destroy(EventMaqra $eventMaqra)
    {
        // Opsional: Pastikan maqra belum digunakan/dicabut oleh peserta (di event_maqra_drawings)
        // Jika sudah ada relasinya, Anda mungkin perlu melakukan validasi sebelum menghapus
        // $hasBeenDrawn = EventMaqraDrawing::where('event_maqra_id', $eventMaqra->id)->exists();
        // if ($hasBeenDrawn) {
        //     return response()->json(['message' => 'Maqra tidak bisa dihapus karena sudah dicabut oleh peserta.'], 422);
        // }

        $eventMaqra->delete();

        return response()->json([
            'message' => 'Data maqra berhasil dihapus.',
        ]);
    }
}